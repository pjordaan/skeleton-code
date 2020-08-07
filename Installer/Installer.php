<?php

namespace Pjordaan\Installer;

use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Script\Event;

class Installer
{
    /**
     * @var array
     */
    private static $packageName;

    /**
     * @var string
     */
    private static $name;

    /**
     * @var string
     */
    private static $email;

    public static function preInstall(Event $event) : void
    {
        $io = $event->getIO();
        $vendorClass = self::ask($io, 'What is the vendor name ?', 'MyVendor');
        $packageClass = self::ask($io, 'What is the package name ?', 'MyPackage');
        self::$name = self::ask($io, 'What is your name ?', self::getUserName());
        self::$email = self::ask($io, 'What is your email address ?', self::getUserEmail());
        $packageName = sprintf('%s/%s', self::camel2dashed($vendorClass), self::camel2dashed($packageClass));
        $json = new JsonFile(Factory::getComposerFile());
        $composerDefinition = self::getDefinition($vendorClass, $packageClass, $packageName, $json);
        self::$packageName = [$vendorClass, $packageClass];
        // Update composer definition
        $json->write($composerDefinition);
        $io->write("<info>composer.json for {$composerDefinition['name']} is created.\n</info>");
    }

    private static function getDefinition(string $vendor, string $package, string $packageName, JsonFile $json) : array
    {
        $composerDefinition = $json->read();
        unset(
            $composerDefinition['autoload']['files'],
            $composerDefinition['scripts']['pre-install-cmd'],
            $composerDefinition['scripts']['post-install-cmd'],
            $composerDefinition['scripts']['pre-update-cmd'],
            $composerDefinition['scripts']['post-create-project-cmd'],
            $composerDefinition['keywords'],
            $composerDefinition['homepage']
        );
        $composerDefinition['name'] = $packageName;
        $composerDefinition['authors'] = [
            [
                'name' => self::$name,
                'email' => self::$email
            ]
        ];
        $composerDefinition['description'] = '';
        $composerDefinition['autoload']['psr-4'] = ["{$vendor}\\{$package}\\" => 'src/'];

        return $composerDefinition;
    }

    private static function camel2dashed(string $name) : string
    {
        return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $name));
    }

    private static function getUserName() : string
    {
        $author = shell_exec('git config --global user.name || git config user.name');

        return $author ? trim($author) : '';
    }

    private static function getUserEmail() : string
    {
        $email = shell_exec('git config --global user.email || git config user.email');

        return $email ? trim($email) : '';
    }
}
