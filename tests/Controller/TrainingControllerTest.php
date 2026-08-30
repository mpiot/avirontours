<?php

declare(strict_types=1);

/*
 * Copyright 2020 Mathieu Piot
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Tests\Controller;

use App\Enum\Feeling;
use App\Enum\RatedPerceivedExertion;
use App\Enum\SportType;
use App\Factory\LicenseFactory;
use App\Factory\TrainingFactory;
use App\Factory\TrainingPhaseFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class TrainingControllerTest extends AppWebTestCase
{
    #[DataProvider('urlProvider')]
    public function testAccessDeniedForAnonymousUser(string $method, string $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $training = TrainingFactory::createOne();
            $url = str_replace('{id}', (string) $training->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    #[DataProvider('urlProvider')]
    public function testAccessDeniedForUnlicensedUser(string $method, string $url): void
    {
        $user = UserFactory::createOne();

        if (mb_strpos($url, '{id}')) {
            $training = TrainingFactory::createOne(['user' => $user]);
            $url = str_replace('{id}', (string) $training->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testIndexTrainings(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        TrainingFactory::createMany(6, [
            'trainedAt' => new \DateTime('monday this week'),
            'user' => $user,
        ]);
        TrainingFactory::createMany(6, [
            'trainedAt' => new \DateTime('-2 months'),
            'user' => $user,
        ]);

        TrainingFactory::createMany(3, [
            'trainedAt' => new \DateTime('monday this week'),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/training');

        $this->assertResponseIsSuccessful();
        $this->assertCount(6, $crawler->filter('#training-list .app-training-session'));
    }

    public function testIndexShowsAPaceOnlyForTheSportsThatHaveOne(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        foreach ([SportType::Rowing, SportType::Yoga] as $sport) {
            TrainingFactory::createOne([
                'user' => $user,
                'sport' => $sport,
                'trainedAt' => new \DateTime('monday this week'),
                'duration' => 36000,
                'distance' => 10000,
            ]);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/training');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString("03:00.0\u{A0}/500m", $this->sessionText($crawler, 'Aviron'));
        $this->assertStringNotContainsString('/500m', $this->sessionText($crawler, 'Yoga'));
    }

    public function testIndexMarksTheSessionsThatHaveNoEffortRating(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $monday = new \DateTime('monday this week');
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'ratedPerceivedExertion' => RatedPerceivedExertion::VeryHard, 'trainedAt' => $monday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Yoga, 'ratedPerceivedExertion' => null, 'trainedAt' => $monday]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/training');

        $this->assertResponseIsSuccessful();

        $rated = $this->sessionText($crawler, 'Aviron');

        $this->assertStringContainsString("Effort perçu\u{A0}:7/10", $rated);
        $this->assertStringNotContainsString('non renseigné', $rated);
        $this->assertStringContainsString('Effort perçu non renseigné', $this->sessionText($crawler, 'Yoga'));
    }

    public function testIndexSplitsTheWeekBySpecificity(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $monday = new \DateTime('monday this week');
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 36000, 'trainedAt' => $monday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Cycling, 'duration' => 18000, 'trainedAt' => $monday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Yoga, 'duration' => 18000, 'trainedAt' => $monday]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/training');

        $this->assertResponseIsSuccessful();

        $legend = $crawler->filter('.app-training-legend')->first()->filter('li')->each(static fn (Crawler $item): string => $item->text());

        $this->assertCount(2, $legend);
        $this->assertStringContainsString('Spécifique 1 séance', $legend[0]);
        $this->assertStringContainsString('Non-spécifique 2 séances', $legend[1]);
    }

    #[DataProvider('endAtProvider')]
    public function testIndexTrainingsAcceptsEndAtQuery(string $endAt): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', "/training?endAt={$endAt}");

        $this->assertResponseIsSuccessful();
    }

    public function testIndexFiltersBySport(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $monday = new \DateTime('monday this week');
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'trainedAt' => $monday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Yoga, 'trainedAt' => $monday]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/training?sport=rowing');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', '1 séance trouvée');
        $this->assertCount(1, $crawler->filter('.app-training-session'));
        $this->assertStringContainsString('Aviron', $this->sessionText($crawler, 'Aviron'));
    }

    public function testIndexFiltersByCommentTextCaseInsensitively(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $monday = new \DateTime('monday this week');
        TrainingFactory::createOne(['user' => $user, 'comment' => 'Sortie longue en HUIT', 'trainedAt' => $monday]);
        TrainingFactory::createOne(['user' => $user, 'comment' => 'Séance technique', 'trainedAt' => $monday]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/training?q=huit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', '1 séance trouvée');
        $this->assertCount(1, $crawler->filter('.app-training-session'));
        $this->assertStringContainsString('Sortie longue en HUIT', $crawler->filter('.app-training-session')->text());
    }

    public function testIndexFiltersByDateRange(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        TrainingFactory::createOne(['user' => $user, 'comment' => 'Stage de mars', 'trainedAt' => new \DateTime('2024-03-10')]);
        TrainingFactory::createOne(['user' => $user, 'comment' => 'Stage de mai', 'trainedAt' => new \DateTime('2024-05-10')]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/training?from=2024-03-01&to=2024-03-31');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', '1 séance trouvée');
        $this->assertCount(1, $crawler->filter('.app-training-session'));

        $session = $crawler->filter('.app-training-session')->text();

        $this->assertStringContainsString('Stage de mars', $session);
        // The flat list spans years, so the row carries the year the week headings used to provide
        $this->assertStringContainsString('2024', $session);
    }

    public function testIndexShowsEmptyStateWhenNoTrainingMatchesTheFilters(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        TrainingFactory::createOne(['user' => $user, 'comment' => 'Sortie du dimanche', 'trainedAt' => new \DateTime('monday this week')]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training?q=introuvable');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Aucun entraînement ne correspond à ces filtres');
    }

    public function testIndexKeepsTheWeekViewWhenFiltersAreSubmittedEmpty(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('monday this week')]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training?from=&to=&sport=&q=');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Semaine');
        $this->assertSelectorTextNotContains('body', 'trouvée');
    }

    #[DataProvider('filterProvider')]
    public function testIndexIgnoresInvalidFilterValues(string $queryString): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', "/training?{$queryString}");

        $this->assertResponseIsSuccessful();
    }

    public function testIndexFilteredResultsArePaginated(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        TrainingFactory::createMany(26, static fn (int $daysAgo): array => [
            'user' => $user,
            'sport' => SportType::Rowing,
            'comment' => 26 === $daysAgo ? 'La toute première' : null,
            'trainedAt' => new \DateTime("-{$daysAgo} days"),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $client->request('GET', '/training?sport=rowing');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', '26 séances trouvées');
        $this->assertSelectorTextNotContains('body', 'La toute première');

        $client->request('GET', '/training?sport=rowing&page=2');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'La toute première');
    }

    public function testShowTrainingDisplaysEverythingRecorded(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne([
            'user' => $user,
            'sport' => SportType::Rowing,
            'duration' => 36000,
            'distance' => 10000,
            'feeling' => Feeling::Good,
            'ratedPerceivedExertion' => RatedPerceivedExertion::VeryHard,
            'strokeRate' => 22,
            'averageHeartRate' => 148,
            'maxHeartRate' => 176,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', "/training/{$training->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('03:00.0 /500m', $crawler->filter('.app-stat-list')->eq(0)->text());

        $intensity = $crawler->filter('.app-stat-list')->eq(1)->text();
        $this->assertStringContainsString('22 c/min', $intensity);
        $this->assertStringContainsString('148 bpm', $intensity);
        $this->assertStringContainsString('max 176', $intensity);

        $rating = $crawler->filter('#rating')->text();
        $this->assertStringContainsString('Bien', $rating);
        $this->assertStringContainsString('7', $rating);
        $this->assertStringContainsString('Très dur', $rating);
        $this->assertCount(0, $crawler->filter('#rating form'));
        $this->assertStringContainsString('Charge 420', $crawler->filter('.app-training-detail')->text());
    }

    public function testShowTrainingAsksForTheRatingWhenSomethingIsMissing(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $complete = TrainingFactory::createOne(['user' => $user, 'feeling' => Feeling::Good, 'ratedPerceivedExertion' => RatedPerceivedExertion::Hard]);
        $noEffort = TrainingFactory::createOne(['user' => $user, 'feeling' => Feeling::Good, 'ratedPerceivedExertion' => null]);
        $noFeeling = TrainingFactory::createOne(['user' => $user, 'feeling' => null, 'ratedPerceivedExertion' => RatedPerceivedExertion::Hard]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', "/training/{$noEffort->getId()}");

        $this->assertCount(1, $crawler->filter('#rating turbo-frame[src$="/edit/rating"]'));

        $crawler = $client->request('GET', "/training/{$noFeeling->getId()}");

        $this->assertCount(1, $crawler->filter('#rating turbo-frame[src$="/edit/rating"]'));

        $crawler = $client->request('GET', "/training/{$complete->getId()}");

        $this->assertCount(0, $crawler->filter('#rating turbo-frame'));
    }

    public function testFeelingHelpAnchorsTheSmileys(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/training/feeling-help');

        $this->assertResponseIsSuccessful();

        $text = $crawler->filter('.app-modal-body')->text();

        $this->assertStringContainsString('pas ce que la séance t\'a coûté', $text);
        $this->assertStringContainsString('je me suis traîné toute la journée', $text);
        $this->assertStringContainsString('une journée ordinaire, ni élan ni frein', $text);
    }

    public function testExertionHelpExplainsBothReadingKeys(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/training/exertion-help');

        $this->assertResponseIsSuccessful();

        $text = $crawler->filter('.app-modal-body')->text();

        $this->assertStringContainsString('phrases courtes, je tiendrais environ une heure', $text);
        $this->assertStringContainsString('3 reps en réserve, technique nette du début à la fin', $text);
        $this->assertStringContainsString("Ce n'est pas le RPE de série", $text);
    }

    public function testShowTrainingGivesWattsToTheErgometerOnly(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $ergometer = TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Ergometer, 'duration' => 36000, 'distance' => 10000]);
        $rowing = TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 36000, 'distance' => 10000]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', "/training/{$ergometer->getId()}");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString('Puissance', $crawler->filter('.app-training-detail')->text());

        $crawler = $client->request('GET', "/training/{$rowing->getId()}");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringNotContainsString('Puissance', $crawler->filter('.app-training-detail')->text());
    }

    public function testShowOtherUserTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', "/training/{$training->getId()}");

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRateTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne([
            'user' => $user,
            'duration' => 36000,
            'feeling' => null,
            'ratedPerceivedExertion' => null,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', "/training/{$training->getId()}/edit/rating");
        $client->submit($crawler->selectButton('Enregistrer')->form([
            'training_edit_rating[feeling]' => Feeling::Good->value,
            'training_edit_rating[ratedPerceivedExertion]' => RatedPerceivedExertion::ExtremelyHard->value,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/vnd.turbo-stream.html; charset=UTF-8');

        $stream = $client->getCrawler();

        $this->assertCount(1, $stream->filter('turbo-stream[action="append"][target="flashes"]'));
        $this->assertStringContainsString('480', $stream->filter('turbo-stream[target="training-load"]')->text());
        $this->assertStringContainsString('Bien', $stream->filter('turbo-stream[target="rating"]')->text());
        $this->assertCount(0, $stream->filter('turbo-stream[target="rating"] form'));

        TrainingFactory::repository()->assert()->exists([
            'id' => $training->getId(),
            'feeling' => Feeling::Good,
            'ratedPerceivedExertion' => RatedPerceivedExertion::ExtremelyHard,
        ]);
    }

    public function testRateTrainingWithHalfAnAnswer(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne([
            'user' => $user,
            'feeling' => null,
            'ratedPerceivedExertion' => null,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', "/training/{$training->getId()}/edit/rating");
        $client->submit($crawler->selectButton('Enregistrer')->form([
            'training_edit_rating[feeling]' => Feeling::Good->value,
        ]));

        $this->assertResponseIsSuccessful();

        $stream = $client->getCrawler();

        $this->assertCount(1, $stream->filter('turbo-stream[action="append"][target="flashes"]'));
        $this->assertCount(1, $stream->filter('turbo-stream[target="training-load"]'));
        $this->assertCount(1, $stream->filter('turbo-stream[target="rating"] form'));

        TrainingFactory::repository()->assert()->exists([
            'id' => $training->getId(),
            'feeling' => Feeling::Good,
            'ratedPerceivedExertion' => null,
        ]);
    }

    public function testRateOtherUserTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('POST', "/training/{$training->getId()}/edit/rating");

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testShowTrainingPhase(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne(['user' => $user]);
        $phases = TrainingPhaseFactory::createSequence([
            ['training' => $training],
            ['training' => $training],
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', "/training/{$training->getId()}/phase/{$phases[1]->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $crawler->filter('turbo-frame#training-phases tbody tr'));
        $this->assertCount(1, $crawler->filter('tbody tr.table-active'));
        $this->assertCount(2, $crawler->filter('canvas[data-controller~="ergometer-chart"]'));
    }

    public function testShowTrainingPhaseOfASinglePhaseTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne(['user' => $user]);
        $phase = TrainingPhaseFactory::createOne([
            'training' => $training,
            'heartRates' => array_fill(0, 12, 140),
            'times' => range(1, 12),
            'paces' => array_fill(0, 12, 1500),
            'strokeRates' => array_fill(0, 12, 20),
            'distances' => range(1, 12),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', "/training/{$training->getId()}/phase/{$phase->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $crawler->filter('canvas[data-controller~="ergometer-chart"]'));
        $this->assertStringContainsString('Fréquence cardiaque', $crawler->filter('turbo-frame#training-phases')->text());
        $this->assertCount(0, $crawler->filter('turbo-frame#training-phases table'));
    }

    public function testShowTrainingPhaseWithoutSeriesHasNoChart(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne(['user' => $user]);
        $phase = TrainingPhaseFactory::new()->withoutSeries()->create(['training' => $training]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', "/training/{$training->getId()}/phase/{$phase->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('canvas'));
        $this->assertStringContainsString('Pas de données', $crawler->filter('turbo-frame#training-phases')->text());
    }

    public function testShowTrainingPhaseOfAnotherTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne(['user' => $user]);
        $phase = TrainingPhaseFactory::createOne(['training' => TrainingFactory::createOne(['user' => $user])]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', "/training/{$training->getId()}/phase/{$phase->getId()}");

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testShowOtherUserTrainingPhase(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $phase = TrainingPhaseFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', "/training/{$phase->getTraining()->getId()}/phase/{$phase->getId()}");

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testNewTrainingOffersEveryChoiceAsRadios(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/training/new');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('form select'));
        $this->assertCount(\count(SportType::cases()), $crawler->filter('#training_sport input[type="radio"]'));
        $this->assertCount(\count(Feeling::cases()) + 1, $crawler->filter('#training_feeling input[type="radio"]'));
        $this->assertCount(\count(RatedPerceivedExertion::cases()) + 1, $crawler->filter('#training_ratedPerceivedExertion input[type="radio"]'));
    }

    public function testNewTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'training[trainedAt]' => '2020-01-15',
            'training[sport]' => SportType::Rowing->value,
            'training[duration]' => '90',
            'training[distance]' => 16.3,
            'training[feeling]' => Feeling::Good->value,
            'training[ratedPerceivedExertion]' => RatedPerceivedExertion::SomewhatHard->value,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseRedirects();

        $training = TrainingFactory::repository()->last();

        $this->assertSame('2020-01-15 00:00', $training->getTrainedAt()->format('Y-m-d H:i'));
        $this->assertSame(SportType::Rowing, $training->getSport());
        $this->assertSame(54000, $training->getDuration());
        $this->assertSame('01:30', $training->getFormattedDuration());
        $this->assertSame(16300, $training->getDistance());
        $this->assertSame(Feeling::Good, $training->getFeeling());
        $this->assertSame(RatedPerceivedExertion::SomewhatHard, $training->getRatedPerceivedExertion());
        $this->assertSame('My little comment...', $training->getComment());
    }

    public function testNewTrainingWithoutDistance(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'training[trainedAt]' => '2020-01-15',
            'training[sport]' => SportType::Rowing->value,
            'training[duration]' => '90',
            'training[distance]' => '',
            'training[feeling]' => Feeling::Good->value,
            'training[ratedPerceivedExertion]' => RatedPerceivedExertion::SomewhatHard->value,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseRedirects();

        $training = TrainingFactory::repository()->last();

        $this->assertSame('2020-01-15 00:00', $training->getTrainedAt()->format('Y-m-d H:i'));
        $this->assertSame(SportType::Rowing, $training->getSport());
        $this->assertSame(54000, $training->getDuration());
        $this->assertSame('01:30', $training->getFormattedDuration());
        $this->assertNull($training->getDistance());
        $this->assertSame(Feeling::Good, $training->getFeeling());
        $this->assertSame(RatedPerceivedExertion::SomewhatHard, $training->getRatedPerceivedExertion());
        $this->assertSame('My little comment...', $training->getComment());
    }

    public function testNewTrainingWithTooLowDistance(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Enregistrer', [
            'training[trainedAt]' => '2020-01-15 14:02',
            'training[sport]' => SportType::Rowing->value,
            'training[duration]' => '90',
            'training[distance]' => 0,
            'training[feeling]' => Feeling::Good->value,
            'training[ratedPerceivedExertion]' => RatedPerceivedExertion::SomewhatHard->value,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur doit être supérieure à 0.', $crawler->filter('#training_distance')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testNewTrainingWithTooLongDistance(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Enregistrer', [
            'training[trainedAt]' => '2020-01-15 14:02',
            'training[sport]' => SportType::Rowing->value,
            'training[duration]' => '90',
            'training[distance]' => 501,
            'training[feeling]' => Feeling::Good->value,
            'training[ratedPerceivedExertion]' => RatedPerceivedExertion::SomewhatHard->value,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Un entraînement doit faire 400km maximum.', $crawler->filter('#training_distance')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testNewTrainingWithoutData(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        // Sport is a radio group: leaving it unanswered means not submitting it.
        $crawler = $client->submitForm('Enregistrer', [
            'training[trainedAt]' => '',
            'training[duration]' => '',
            'training[distance]' => '',
            'training[comment]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#training_sport')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#training_trainedAt')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Un entraînement doit durer au moins 5 minutes.', $crawler->filter('#training_duration')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(3, $crawler->filter('.invalid-feedback'));
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testNewTrainingWithTooShortDuration(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Enregistrer', [
            'training[trainedAt]' => '2020-01-15',
            'training[sport]' => SportType::Rowing->value,
            'training[duration]' => '2',
            'training[distance]' => 16.3,
            'training[feeling]' => Feeling::Good->value,
            'training[ratedPerceivedExertion]' => RatedPerceivedExertion::SomewhatHard->value,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Un entraînement doit durer au moins 5 minutes.', $crawler->filter('#training_duration')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testNewTrainingWithAnUnreadableDuration(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Enregistrer', [
            'training[trainedAt]' => '2020-01-15',
            'training[sport]' => SportType::Rowing->value,
            'training[duration]' => 'une heure et demie',
            'training[distance]' => 16.3,
            'training[feeling]' => Feeling::Good->value,
            'training[ratedPerceivedExertion]' => RatedPerceivedExertion::SomewhatHard->value,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Indiquez une durée en minutes, par exemple 90.', $crawler->filter('#training_duration')->closest('.mb-3')->filter('.invalid-feedback')->text());
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testNewTrainingReadsABareNumberAsMinutes(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'training[trainedAt]' => '2020-01-15',
            'training[sport]' => SportType::Rowing->value,
            'training[duration]' => '90',
            'training[distance]' => 16.3,
        ]);

        $this->assertResponseRedirects();
        $this->assertSame('01:30', TrainingFactory::repository()->last()->getFormattedDuration());
    }

    public function testEditTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne(['user' => $user]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', "/training/{$training->getId()}/edit");

        $this->assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'training[trainedAt]' => '2020-01-15',
            'training[sport]' => SportType::Rowing->value,
            'training[duration]' => '90',
            'training[distance]' => 16.3,
            'training[feeling]' => Feeling::Good->value,
            'training[ratedPerceivedExertion]' => RatedPerceivedExertion::SomewhatHard->value,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseRedirects();
        $this->assertSame('2020-01-15 00:00', $training->getTrainedAt()->format('Y-m-d H:i'));
        $this->assertSame(SportType::Rowing, $training->getSport());
        $this->assertSame(54000, $training->getDuration());
        $this->assertSame('01:30', $training->getFormattedDuration());
        $this->assertSame(16300, $training->getDistance());
        $this->assertSame(Feeling::Good, $training->getFeeling());
        $this->assertSame(RatedPerceivedExertion::SomewhatHard, $training->getRatedPerceivedExertion());
        $this->assertSame('My little comment...', $training->getComment());
    }

    public function testEditImportedTrainingLocksWhatTheErgometerMeasured(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne(['user' => $user, 'duration' => 54000]);
        TrainingPhaseFactory::createOne(['training' => $training]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', "/training/{$training->getId()}/edit");

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString("viennent de l'ergomètre et ne sont pas modifiables", $crawler->filter('.alert')->text());
        $this->assertSame('90', $crawler->filter('#training_duration')->attr('value'));
        $this->assertNotNull($crawler->filter('#training_duration')->attr('disabled'));
    }

    public function testEditOtherUserTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', "/training/{$training->getId()}/edit");

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteTraining(): void
    {
        $training = TrainingFactory::createOne([
            'user' => $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser(),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', "/training/{$training->getId()}");

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/training');

        TrainingFactory::repository()->assert()->notExists($training);
    }

    public function testImportConceptLogbookRequiresConnectedAccount(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/import/concept-logbook');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertCount(0, self::getContainer()->get('messenger.transport.async')->getSent());
    }

    public function testImportConceptLogbookDispatchesForConnectedAccount(): void
    {
        $user = UserFactory::createOne(['concept2RefreshToken' => 'a-refresh-token']);
        LicenseFactory::new()->annualActive()->withValidLicense()->create(['user' => $user]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/import/concept-logbook');

        $this->assertResponseRedirects('/training');
        $this->assertCount(1, self::getContainer()->get('messenger.transport.async')->getSent());

        $client->followRedirect();
        $this->assertSelectorTextContains('.toast-body', 'en cours de synchronisation');
    }

    private function sessionText(Crawler $crawler, string $sportLabel): string
    {
        $sessions = $crawler->filter('.app-training-session')->each(static fn (Crawler $session): string => $session->text());
        $session = current(array_filter($sessions, static fn (string $text): bool => str_contains($text, $sportLabel)));

        if (false === $session) {
            self::fail("No \"{$sportLabel}\" session on the index.");
        }

        return $session;
    }

    public static function urlProvider(): \Generator
    {
        yield ['GET', '/training'];
        yield ['GET', '/training/{id}'];
        yield ['GET', '/training/new'];
        yield ['GET', '/training/feeling-help'];
        yield ['GET', '/training/exertion-help'];
        yield ['POST', '/training/new'];
        yield ['GET', '/training/{id}/edit'];
        yield ['POST', '/training/{id}/edit'];
        yield ['POST', '/training/{id}'];
    }

    public static function endAtProvider(): \Generator
    {
        yield 'valid date' => ['2020-01-15'];
        yield 'out-of-range month and day' => ['9999-99-99'];
        yield 'impossible day' => ['2020-02-30'];
    }

    public static function filterProvider(): \Generator
    {
        yield 'out-of-range from' => ['from=9999-99-99'];
        yield 'out-of-range to' => ['to=2020-99-99'];
        yield 'unknown sport' => ['sport=quidditch'];
        yield 'blank comment query' => ['q=%20%20'];
    }
}
