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

namespace App\Factory;

use App\Entity\User;
use App\Repository\UserRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static User|Proxy                     createOne(array $attributes = [])
 * @method static User[]|Proxy[]                 createMany(int $number, $attributes = [])
 * @method static User|Proxy                     findOrCreate(array $attributes)
 * @method static User|Proxy                     random(array $attributes = [])
 * @method static User|Proxy                     randomOrCreate(array $attributes = [])
 * @method static User[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static User[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static UserRepository|RepositoryProxy repository()
 * @method        User|Proxy                     create($attributes = [])
 */
final class UserFactory extends ModelFactory
{
    public const PASSWORD = 'engage';

    public function major(): self
    {
        return $this->addState([
            'birthday' => self::faker()->dateTimeBetween('-80 years', '-20 years'),
        ]);
    }

    public function minor(): self
    {
        return $this->addState([
            'birthday' => self::faker()->dateTimeBetween('-17 years', '-11 years'),
            'phoneNumber' => self::faker()->phoneNumber(),
        ]);
    }

    public function withAnnualActiveLicense(): self
    {
        return $this->addState([
            'licenses' => LicenseFactory::new()->annualActive()->many(1),
        ]);
    }

    public function withAnnualInactiveLicense(): self
    {
        return $this->addState([
            'licenses' => LicenseFactory::new()->annualInactive()->many(1),
        ]);
    }

    protected function getDefaults(): array
    {
        return [
            'password' => '$argon2id$v=19$m=10,t=3,p=1$504u7GDCM160iitiwetjvQ$6MguL3z0WsHOSxjKI6NhcPi4QdBFNlff/xrck+m975I',
            'gender' => self::faker()->randomElement(User::getAvailableGenders()),
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'email' => self::faker()->email(),
            'nationality' => self::faker()->countryCode(),
            'birthday' => self::faker()->dateTimeBetween('-80 years', '-20 years'),
            'laneNumber' => '5',
            'laneType' => 'Avenue',
            'laneName' => 'de Florence',
            'postalCode' => PostalCodeFactory::findOrCreate(['postalCode' => '37000', 'city' => 'TOURS'])->getPostalCode(),
            'city' => PostalCodeFactory::findOrCreate(['postalCode' => '37000', 'city' => 'TOURS'])->getCity(),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->beforeInstantiate(function(User $user) {})
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
