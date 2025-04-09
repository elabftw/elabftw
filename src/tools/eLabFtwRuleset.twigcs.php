<?php

/**
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace FriendsOfTwig\Twigcs\Ruleset;

use FriendsOfTwig\Twigcs\RegEngine\RulesetBuilder;
use FriendsOfTwig\Twigcs\RegEngine\RulesetConfigurator;
use FriendsOfTwig\Twigcs\Rule;
use FriendsOfTwig\Twigcs\TemplateResolver\NullResolver;
use FriendsOfTwig\Twigcs\TemplateResolver\TemplateResolverInterface;
use FriendsOfTwig\Twigcs\Validator\Violation;
use Override;

/**
 * eLabFTW ruleset for twigcs
 */
final class ELabFtwRuleset implements RulesetInterface, TemplateResolverAwareInterface
{
    private TemplateResolverInterface $resolver;

    public function __construct(private int $twigMajorVersion)
    {
        $this->resolver = new NullResolver();
    }

    #[Override]
    public function getRules()
    {
        $configurator = new RulesetConfigurator();
        $configurator->setTwigMajorVersion($this->twigMajorVersion);
        $builder = new RulesetBuilder($configurator);

        return array(
            new Rule\ForbiddenFunctions(Violation::SEVERITY_ERROR, array('dump')),
            // allow CamelCase, deactivate Rule\LowerCaseVariable
            //new Rule\LowerCaseVariable(Violation::SEVERITY_ERROR),
            new Rule\RegEngineRule(Violation::SEVERITY_ERROR, $builder->build()),
            new Rule\TrailingSpace(Violation::SEVERITY_ERROR),
            new Rule\UnusedMacro(Violation::SEVERITY_WARNING, $this->resolver),
            new Rule\UnusedVariable(Violation::SEVERITY_WARNING, $this->resolver),
        );
    }

    #[Override]
    public function setTemplateResolver(TemplateResolverInterface $resolver): void
    {
        $this->resolver = $resolver;
    }
}
