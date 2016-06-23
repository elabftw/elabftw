<?php

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Keywords;

use Symfony\Component\Yaml\Yaml;

/**
 * Cucumber-translations reader.
 *
 * $keywords = new Behat\Gherkin\Keywords\CucumberKeywords($i18nYmlPath);
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class CucumberKeywords extends ArrayKeywords
{
    /**
     * Initializes holder with yaml string OR file.
     *
     * @param string $yaml Yaml string
     */
    public function __construct($yaml)
    {
        parent::__construct(Yaml::parse($yaml));
    }

    /**
     * Returns Feature keywords (splitted by "|").
     *
     * @return string
     */
    public function getGivenKeywords()
    {
        return $this->prepareStepString(parent::getGivenKeywords());
    }

    /**
     * Returns When keywords (splitted by "|").
     *
     * @return string
     */
    public function getWhenKeywords()
    {
        return $this->prepareStepString(parent::getWhenKeywords());
    }

    /**
     * Returns Then keywords (splitted by "|").
     *
     * @return string
     */
    public function getThenKeywords()
    {
        return $this->prepareStepString(parent::getThenKeywords());
    }

    /**
     * Returns And keywords (splitted by "|").
     *
     * @return string
     */
    public function getAndKeywords()
    {
        return $this->prepareStepString(parent::getAndKeywords());
    }

    /**
     * Returns But keywords (splitted by "|").
     *
     * @return string
     */
    public function getButKeywords()
    {
        return $this->prepareStepString(parent::getButKeywords());
    }

    /**
     * Trim *| from the begining of the list.
     *
     * @param string $keywordsString Keywords string
     *
     * @return string
     */
    private function prepareStepString($keywordsString)
    {
        if (0 === mb_strpos($keywordsString, '*|', 0, 'UTF-8')) {
            $keywordsString = mb_substr($keywordsString, 2, mb_strlen($keywordsString, 'utf8') - 2, 'utf8');
        }

        return $keywordsString;
    }
}
