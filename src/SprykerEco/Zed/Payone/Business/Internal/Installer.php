<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Payone\Business\Internal;

use Generated\Shared\Transfer\LocaleTransfer;
use Spryker\Zed\Installer\Business\Model\AbstractInstaller;
use SprykerEco\Zed\Payone\Dependency\Facade\PayoneToGlossaryInterface;
use SprykerEco\Zed\Payone\PayoneConfig;
use Symfony\Component\Yaml\Yaml;

class Installer extends AbstractInstaller
{
    /**
     * @var \SprykerEco\Zed\Payone\Dependency\Facade\PayoneToGlossaryInterface
     */
    protected $glossary;

    /**
     * @var \SprykerEco\Zed\Payone\PayoneConfig
     */
    protected $config;

    /**
     * @param \SprykerEco\Zed\Payone\Dependency\Facade\PayoneToGlossaryInterface $glossary
     * @param \SprykerEco\Zed\Payone\PayoneConfig $config
     */
    public function __construct(PayoneToGlossaryInterface $glossary, PayoneConfig $config)
    {
        $this->glossary = $glossary;
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function install()
    {
        $fileName = $this->config->getTranslationFilePath();
        $translations = $this->parseYamlFile($fileName);

        return $this->installKeysAndTranslations($translations);
    }

    /**
     * @param string $filePath
     *
     * @return array
     */
    protected function parseYamlFile($filePath)
    {
        $yamlParser = new Yaml();

        return $yamlParser->parse(file_get_contents($filePath));
    }

    /**
     * @param array $translations
     *
     * @return array
     */
    protected function installKeysAndTranslations(array $translations)
    {
        $results = [];
        foreach ($translations as $keyName => $data) {
            $results[$keyName]['created'] = false;
            if (!$this->glossary->hasKey($keyName)) {
                $this->glossary->createKey($keyName);
                $results[$keyName]['created'] = true;
            }

            foreach ($data['translations'] as $localeName => $text) {
                $results[$keyName]['translation'][$localeName] = $this->addTranslation($localeName, $keyName, $text);
            }
        }

        return $results;
    }

    /**
     * @param string $localeName
     * @param string $keyName
     * @param string $text
     *
     * @return array
     */
    protected function addTranslation($localeName, $keyName, $text)
    {
        $locale = new LocaleTransfer();
        $locale->setLocaleName($localeName);
        $translation = [];

        $translation['text'] = $text;
        $translation['created'] = false;
        $translation['updated'] = false;

        if (!$this->glossary->hasTranslation($keyName, $locale)) {
            $this->glossary->createAndTouchTranslation($keyName, $locale, $text, true);
            $translation['created'] = true;

            return $translation;
        }

        $this->glossary->updateAndTouchTranslation($keyName, $locale, $text, true);
        $translation['updated'] = true;

        return $translation;
    }
}
