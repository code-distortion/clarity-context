<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support\Framework;

use CodeDistortion\ClarityContext\Exceptions\ClarityContextInitialisationException;
use CodeDistortion\ClarityContext\Settings;
use CodeDistortion\ClarityContext\Support\Framework\Framework;
use CodeDistortion\ClarityContext\Support\InternalSettings;
use CodeDistortion\ClarityContext\Tests\LaravelTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the Laravel framework config integration.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class LaravelFrameworkConfigUnitTest extends LaravelTestCase
{
    /**
     * Test that the framework config object is cached, that it returns the same instance each time.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_framework_config_caching(): void
    {
        self::assertSame(Framework::config(), Framework::config());
    }



    /**
     * Test that the project-root-directory is detected properly.
     *
     * @test
     *
     * @return void
     * @throws ClarityContextInitialisationException Doesn't throw this, but phpcs expects this to be here.
     */
    #[Test]
    public static function test_project_root_dir_detection(): void
    {
        self::assertSame(
            realpath(__DIR__ . '/../../../../') . DIRECTORY_SEPARATOR,
            Framework::config()->getProjectRootDir()
        );
    }



    /**
     * Test the framework config crud functionality.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_framework_config_crud(): void
    {
        $config = Framework::config();
        $key = 'a.key';



        // retrieving a BOOLEAN

        // when the value is a string - returns null
        $config->updateConfig([$key => 'abc']);
        self::assertNull($config->pickConfigBoolean($key));

        // when the value is a string with commas - returns null
        $config->updateConfig([$key => 'abc,def']);
        self::assertNull($config->pickConfigBoolean($key));

        // when the value is an empty string - returns null
        $config->updateConfig([$key => '']);
        self::assertNull($config->pickConfigBoolean($key));

        // when the value is null - returns null
        $config->updateConfig([$key => null]);
        self::assertNull($config->pickConfigBoolean($key));

        // when the value is an array - returns null
        $config->updateConfig([$key => ['abc' => 'def']]);
        self::assertNull($config->pickConfigBoolean($key));

        // when the value is an empty array - returns null
        $config->updateConfig([$key => []]);
        self::assertNull($config->pickConfigBoolean($key));

        // when the value is true - returns true
        $config->updateConfig([$key => true]);
        self::assertTrue($config->pickConfigBoolean($key));

        // when the value is false - returns false
        $config->updateConfig([$key => false]);
        self::assertFalse($config->pickConfigBoolean($key));

        // when the value is an integer - returns null
        $config->updateConfig([$key => 123]);
        self::assertNull($config->pickConfigBoolean($key));

        // when the value is a float - returns null
        $config->updateConfig([$key => 123.456]);
        self::assertNull($config->pickConfigBoolean($key));



        // retrieving a STRING

        // when the value is a string - returns the string
        $config->updateConfig([$key => 'abc']);
        self::assertSame('abc', $config->pickConfigString($key));

        // when the value is a string with commas - returns the string
        $config->updateConfig([$key => 'abc,def']);
        self::assertSame('abc,def', $config->pickConfigString($key));

        // when the value is an empty string - returns null
        $config->updateConfig([$key => '']);
        self::assertNull($config->pickConfigString($key));

        // when the value is null - returns null
        $config->updateConfig([$key => null]);
        self::assertNull($config->pickConfigString($key));

        // when the value is an array - returns null
        $config->updateConfig([$key => ['abc' => 'def']]);
        self::assertNull($config->pickConfigString($key));

        // when the value is an empty array - returns null
        $config->updateConfig([$key => []]);
        self::assertNull($config->pickConfigString($key));

        // when the value is true - returns null
        $config->updateConfig([$key => true]);
        self::assertNull($config->pickConfigString($key));

        // when the value is false - returns null
        $config->updateConfig([$key => false]);
        self::assertNull($config->pickConfigString($key));

        // when the value is an integer - returns null
        $config->updateConfig([$key => 123]);
        self::assertNull($config->pickConfigString($key));

        // when the value is a float - returns null
        $config->updateConfig([$key => 123.456]);
        self::assertNull($config->pickConfigString($key));



        // retrieving an ARRAY

        // when the value is a string - returns an array with the string as the value
        $config->updateConfig([$key => 'abc']);
        self::assertSame(['abc'], $config->pickConfigStringArray($key));

        // when the value is a string with commas - returns an array with the string as the value
        $config->updateConfig([$key => 'abc,def']);
        self::assertSame(['abc,def'], $config->pickConfigStringArray($key));

        // when the value is an empty string - returns an empty array
        $config->updateConfig([$key => '']);
        self::assertSame([], $config->pickConfigStringArray($key));

        // when the value is null - returns an empty array
        $config->updateConfig([$key => null]);
        self::assertSame([], $config->pickConfigStringArray($key));

        // when the value is an array - returns the array
        $config->updateConfig([$key => ['abc' => 'def']]);
        self::assertSame(['abc' => 'def'], $config->pickConfigStringArray($key));

        // when the value is an empty array - returns an empty array
        $config->updateConfig([$key => []]);
        self::assertSame([], $config->pickConfigStringArray($key));

        // when the value is true - returns an empty array
        $config->updateConfig([$key => true]);
        self::assertSame([], $config->pickConfigStringArray($key));

        // when the value is false - returns an empty array
        $config->updateConfig([$key => false]);
        self::assertSame([], $config->pickConfigStringArray($key));

        // when the value is an integer - returns an empty array
        $config->updateConfig([$key => 123]);
        self::assertSame([], $config->pickConfigStringArray($key));

        // when the value is a float - returns an empty array
        $config->updateConfig([$key => 123.456]);
        self::assertSame([], $config->pickConfigStringArray($key));
    }

    /**
     * Test the particular values that the framework config fetches.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_framework_config_settings(): void
    {
        $config = Framework::config();
        $config->updateConfig(['logging.default' => 'default-channel']);



        // getEnabled()
        $config->updateConfig([InternalSettings::LARAVEL_CONTEXT__CONFIG_NAME . '.enabled' => null]);
        self::assertNull($config->getEnabled()); // defaults to null
        $config->updateConfig([InternalSettings::LARAVEL_CONTEXT__CONFIG_NAME . '.enabled' => true]);
        self::assertTrue($config->getEnabled());
        $config->updateConfig([InternalSettings::LARAVEL_CONTEXT__CONFIG_NAME . '.enabled' => false]);
        self::assertFalse($config->getEnabled());



        // getChannelsWhenKnown()
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => null]);
        self::assertSame([], $config->getChannelsWhenKnown()); // defaults to []
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => []]);
        self::assertSame([], $config->getChannelsWhenKnown());
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => ['abc']]);
        self::assertSame(['abc'], $config->getChannelsWhenKnown());
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => 'ab,c']);
        self::assertSame(['ab', 'c'], $config->getChannelsWhenKnown());
        $config->updateConfig(
            [InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => ['abc', 'def']]
        );
        self::assertSame(['abc', 'def'], $config->getChannelsWhenKnown());



        // getChannelsWhenNotKnown()
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => null]);
        self::assertSame([], $config->getChannelsWhenNotKnown()); // defaults to []
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => []]);
        self::assertSame([], $config->getChannelsWhenNotKnown());
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => ['abc']]);
        self::assertSame(['abc'], $config->getChannelsWhenNotKnown());
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => 'ab,c']);
        self::assertSame(['ab', 'c'], $config->getChannelsWhenNotKnown());
        $config->updateConfig(
            [InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => ['abc', 'def']]
        );
        self::assertSame(['abc', 'def'], $config->getChannelsWhenNotKnown());



        // pickBestChannels()
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => ['abc']]);
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => ['def']]);
        self::assertSame(['abc'], $config->pickBestChannels(true));
        self::assertSame(['def'], $config->pickBestChannels(false));

        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => ['abc']]);
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => []]);
        self::assertSame(['abc'], $config->pickBestChannels(true));
        self::assertSame(['default-channel'], $config->pickBestChannels(false));

        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => ['abc']]);
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => null]);
        self::assertSame(['abc'], $config->pickBestChannels(true));
        self::assertSame(['default-channel'], $config->pickBestChannels(false));

        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => []]);
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => ['def']]);
        self::assertSame(['default-channel'], $config->pickBestChannels(true));
        self::assertSame(['def'], $config->pickBestChannels(false));

        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => null]);
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => ['def']]);
        self::assertSame(['default-channel'], $config->pickBestChannels(true));
        self::assertSame(['def'], $config->pickBestChannels(false));

        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => []]);
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => []]);
        self::assertSame(['default-channel'], $config->pickBestChannels(true));
        self::assertSame(['default-channel'], $config->pickBestChannels(false));

        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => null]);
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => null]);
        self::assertSame(['default-channel'], $config->pickBestChannels(true));
        self::assertSame(['default-channel'], $config->pickBestChannels(false));



        // getLevelWhenKnown()
        $key = InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.level.when_known';
        $config->updateConfig([$key => null]);
        self::assertNull($config->getLevelWhenKnown());
        $config->updateConfig([$key => Settings::REPORTING_LEVEL_INFO]);
        self::assertSame(Settings::REPORTING_LEVEL_INFO, $config->getLevelWhenKnown());



        // getLevelWhenNotKnown()
        $key = InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.level.when_not_known';
        $config->updateConfig([$key => null]);
        self::assertNull($config->getLevelWhenNotKnown());
        $config->updateConfig([$key => Settings::REPORTING_LEVEL_INFO]);
        self::assertSame(Settings::REPORTING_LEVEL_INFO, $config->getLevelWhenNotKnown());



        // pickBestLevel()
        $knownKey = InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.level.when_known';
        $notKnownKey = InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.level.when_not_known';
        $config->updateConfig([$knownKey => Settings::REPORTING_LEVEL_INFO]);
        $config->updateConfig([$notKnownKey => Settings::REPORTING_LEVEL_DEBUG]);
        self::assertSame(Settings::REPORTING_LEVEL_INFO, $config->pickBestLevel(true));
        self::assertSame(Settings::REPORTING_LEVEL_DEBUG, $config->pickBestLevel(false));

        $config->updateConfig([$knownKey => Settings::REPORTING_LEVEL_INFO]);
        $config->updateConfig([$notKnownKey => null]);
        self::assertSame(Settings::REPORTING_LEVEL_INFO, $config->pickBestLevel(true));
        self::assertSame(null, $config->pickBestLevel(false));

        $config->updateConfig([$knownKey => Settings::REPORTING_LEVEL_INFO]);
        $config->updateConfig([$notKnownKey => null]);
        self::assertSame(Settings::REPORTING_LEVEL_INFO, $config->pickBestLevel(true));
        self::assertSame(null, $config->pickBestLevel(false));

        $config->updateConfig([$knownKey => null]);
        $config->updateConfig([$notKnownKey => Settings::REPORTING_LEVEL_DEBUG]);
        self::assertSame(null, $config->pickBestLevel(true));
        self::assertSame(Settings::REPORTING_LEVEL_DEBUG, $config->pickBestLevel(false));

        $config->updateConfig([$knownKey => null]);
        $config->updateConfig([$notKnownKey => null]);
        self::assertSame(null, $config->pickBestLevel(true));
        self::assertSame(null, $config->pickBestLevel(false));

        $config->updateConfig([$knownKey => null]);
        $config->updateConfig([$notKnownKey => null]);
        self::assertSame(null, $config->pickBestLevel(true));
        self::assertSame(null, $config->pickBestLevel(false));


        $config->updateConfig([$knownKey => 'invalid1']);
        $config->updateConfig([$notKnownKey => 'invalid2']);

        $caughtException = false;
        try {
            self::assertSame(null, $config->pickBestLevel(true));
        } catch (ClarityContextInitialisationException) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);

        $caughtException = false;
        try {
            self::assertSame(null, $config->pickBestLevel(false));
        } catch (ClarityContextInitialisationException) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);



        // getReport
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.report' => null]);
        self::assertNull($config->getReport()); // defaults to null
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.report' => true]);
        self::assertTrue($config->getReport());
        $config->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.report' => false]);
        self::assertFalse($config->getReport());
    }
}
