<?php

$fileHeaderComment = <<<'COMMENT'
    Copyright 2020 Mathieu Piot

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
    COMMENT;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('bin')
    ->exclude('config')
    ->exclude('migrations')
    ->exclude('node_modules')
    ->exclude('public')
    ->notName('Kernel.php')
    ->notName('bootstrap.php')
    ->exclude('var')
    ->exclude('vendor')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        // Sets
        '@Symfony' => true,
        '@Symfony:risky' => true,
        // Alias
        'mb_str_functions' => true,
        // Class Notation
        'class_attributes_separation' => ['elements' => [
            'method' => 'one',
            'property' => 'one',
            'trait_import' => 'none',
            'case' => 'none',
        ]],
        'ordered_class_elements' => ['order' => [
            'use_trait',
            'case',
            'constant_public',
            'constant_protected',
            'constant_private',
            'property_public_static',
            'property_protected_static',
            'property_private_static',
            'property',
            'construct',
            'destruct',
            'magic',
            'method_public_abstract',
            'method_protected_abstract',
            'method_public',
            'method_protected',
            'method_private',
            'method_public_abstract_static',
            'method_protected_abstract_static',
            'method_public_static',
            'method_protected_static',
            'method_private_static',
        ]],
        // Comment
        'comment_to_phpdoc' => true,
        'header_comment' => ['header' => $fileHeaderComment, 'separate' => 'both'],
        'multiline_comment_opening_closing' => true,
        // Control Structure
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        // Import
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
        // PHPDoc
        'align_multiline_comment' => ['comment_type' => 'all_multiline'],
        'phpdoc_order' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_var_annotation_correct_order' => true,
        // PHPUnit
        'php_unit_strict' => true,
        // String Notation
        'explicit_string_variable' => true,
        'no_useless_return' => true,
        'return_assignment' => true,
        // Strict
        'strict_comparison' => true,
        'strict_param' => true,
        // String Notation
        'heredoc_to_nowdoc' => true,
        // Whitespace
        'array_indentation' => true,
        'method_chaining_indentation' => true,
    ])
    ->setFinder($finder)
;
