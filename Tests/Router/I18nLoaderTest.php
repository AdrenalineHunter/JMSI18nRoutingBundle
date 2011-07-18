<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\I18nRoutingBundle\Tests\Router;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\IdentityTranslator;
use JMS\I18nRoutingBundle\Router\I18nLoader;

class I18nLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $col = new RouteCollection();
        $col->add('contact', new Route('/contact'));
        $i18nCol = $this->getLoader()->load($col);

        $this->assertEquals(2, count($i18nCol->all()));

        $de = $i18nCol->get('de_contact');
        $this->assertEquals('/kontakt', $de->getPattern());
        $this->assertEquals('de', $de->getDefault('_locale'));

        $en = $i18nCol->get('en_contact');
        $this->assertEquals('/contact', $en->getPattern());
        $this->assertEquals('en', $en->getDefault('_locale'));
    }

    public function testLoadDoesNotRemoveOriginalIfNotAllRoutesHaveTranslations()
    {
        $col = new RouteCollection();
        $col->add('support', new Route('/support'));
        $i18nCol = $this->getLoader()->load($col);

        $this->assertEquals(3, count($i18nCol->all()));

        $de = $i18nCol->get('de_support');
        $this->assertEquals('/support', $de->getPattern());

        $en = $i18nCol->get('en_support');
        $this->assertEquals('/support', $en->getPattern());
    }

    public function testLoadDoesNotAddI18nRoutesIfI18nIsFalse()
    {
        $col = new RouteCollection();
        $col->add('route', new Route('/no-i18n', array(), array(), array('i18n' => false)));
        $i18nCol = $this->getLoader()->load($col);

        $this->assertEquals(1, count($i18nCol->all()));
        $this->assertNull($i18nCol->get('route')->getDefault('_locale'));
    }

    public function testLoadUsesOriginalTranslationIfNoTranslationExists()
    {
        $col = new RouteCollection();
        $col->add('untranslated_route', new Route('/not-translated'));
        $i18nCol = $this->getLoader()->load($col);

        $this->assertEquals(3, count($i18nCol->all()));
        $this->assertEquals('/not-translated', $i18nCol->get('de_untranslated_route')->getPattern());
        $this->assertEquals('/not-translated', $i18nCol->get('en_untranslated_route')->getPattern());
    }

    public function testLoadIfRouteIsNotTranslatedToAllLocales()
    {
        $col = new RouteCollection();
        $col->add('route', new Route('/not-available-everywhere', array(), array(), array('i18n_locales' => array('en'))));
        $i18nCol = $this->getLoader()->load($col);

        $this->assertEquals(array('en_route'), array_keys($i18nCol->all()));
    }

    private function getLoader()
    {
        $translator = new Translator('en', new MessageSelector());
        $translator->addLoader('yml', new YamlFileLoader());
        $translator->addResource('yml', file_get_contents(__DIR__.'/Fixture/routes.de.yml'), 'de', 'routes');
        $translator->addResource('yml', file_get_contents(__DIR__.'/Fixture/routes.en.yml'), 'en', 'routes');

        return new I18nLoader($translator, array('en', 'de'), 'routes', sys_get_temp_dir());
    }
}