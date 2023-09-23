<?php declare(strict_types=1);
/**
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

use FriendsOfTwig\Twigcs\Config\Config;
use FriendsOfTwig\Twigcs\Finder\TemplateFinder;
use FriendsOfTwig\Twigcs\Ruleset\ELabFtwRuleset;
use FriendsOfTwig\Twigcs\TemplateResolver\FileResolver;

$templateDir = __DIR__ . '/src/templates';

return Config::create()
    ->setName('twigcs')
    ->setSeverity('warning')
    ->setReporter('console')
    ->setFinder(TemplateFinder::create()
        ->in($templateDir)
        ->name('*.html')
        ->sortByName()
    )
    ->setTemplateResolver(new FileResolver($templateDir))
    ->setRuleSet(ELabFtwRuleset::class)
;
