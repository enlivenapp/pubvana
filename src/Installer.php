<?php
namespace Pubvana;

use Composer\Script\Event;

/**
 * Pubvana Composer Installer
 *
 * @author     Enliven Applications <https://github.com/enlivenapp>
 * @author     Kenji Suzuki <https://github.com/kenjis>
 * @license    MIT License
 * @copyright  2018 Enliven Applications
 * @copyright  2015 Kenji Suzuki
 * @link       https://github.com/enlivenapp/Pubvana-Composer-Installer
 */
class Installer
{
    /**
     * Composer post install script
     *
     * @param Event $event
     */
    public static function postInstall(Event $event = null)
    {
        // Copy Pubvana Core files
        self::recursiveCopy('vendor/enlivenapp/pubvana/pubvana', 'pubvana');

        // Copy files to project root
        copy('vendor/enlivenapp/pubvana/index.php', '/index.php');
        //copy('vendor/codeigniter/framework/.gitignore', '.gitignore');
        
        // Fix paths in index.php
        // we keep Codeigniter in the 
        // vendor dir for easier updating
        $file = '/index.php';
        $contents = file_get_contents($file);
        $contents = str_replace(
            '$application_folder = \'application\';',
            '$application_folder = \'pubvana\';',
            $contents
        );

        $contents = str_replace(
            '$system_path = \'system\';',
            '$system_path = \'vendor/codeigniter/framework/system\';',
            $contents
        );


        file_put_contents($file, $contents);

        // The config & db files are actually managed by the Pubvana installer, 
        // so we'll edit the config.php.bak file so it's correct for installation.
        // in the deleteSelf method below we remove the original files from CodeIgniter.
        $file = 'pubvana/config/config.php.bak';

        // Enable Composer Autoloader
        $contents = file_get_contents($file);
        $contents = str_replace(
            '$config[\'composer_autoload\'] = FALSE;',
            '$config[\'composer_autoload\'] = realpath(APPPATH . \'../vendor/autoload.php\');',
            $contents
        );

        // change subclass_prefix
        $contents = str_replace(
            '$config[\'subclass_prefix\'] = \'MY_\';',
            '$config[\'subclass_prefix\'] = \'PV_\';',
            $contents
        );

        file_put_contents($file, $contents);

        // Now that composer has done it's things,
        // we need to deal with how to update, so we
        // use another composer.json file for updates
        // rather than installing. 
        copy('composer.json.dist', 'composer.json');

        // Run composer update
        //self::composerUpdate();

        // install translations
        self::installTranslations();

        // Delete unneeded files
        self::deleteSelf();

        // Show message
        self::showMessage($event);
    }


    public static function postUpdate(Event $event = null)
    {
        // Copy new Pubvana Core files
        //self::recursiveCopy('vendor/enlivenapp/pubvana/pubvana', 'pubvana');

        // update translations
        //self::installTranslations();

        // Show message
       // self::showUpdateMessage($event);

    }

    private static function composerUpdate()
    {
        passthru('composer update');
    }

    // this installs, but also updates by overwriting
    private static function installTranslations()
    {
        passthru('php bin/install.php translations master');
    }

    /**
     * Composer post install script
     *
     * @param Event $event
     */
    private static function showMessage(Event $event = null)
    {
        $io = $event->getIO();
        $io->write('==================================================');
        $io->write('<info>Congratulations! Pubvana has been installed!</info>');
        $io->write('<info>Web Installer: Go to your site in your web browser</info>');
        $io->write('==================================================');
        $io->write('<info>Developers: Other third party libraries are available; type:</info>');
        $io->write('$ cd <pubvana_project_folder>');
        $io->write('$ php bin/install.php');
        $io->write('<info>The above command will show the help message.</info>');
        $io->write('See <https://github.com/enlivenapp/Pubvana-Composer-Installer> for  more details');
        $io->write('==================================================');
    }

    /**
     * Composer post update script
     *
     * @param Event $event
     */
    private static function showUpdateMessage(Event $event = null)
    {
        $io = $event->getIO();
        $io->write('==================================================');
        $io->write('<info>Congratulations! Pubvana has been updated!</info>');
        $io->write('<info>Please log in to your admin panel to complete automated updates.</info>');
        $io->write('==================================================');
    }


    private static function deleteSelf()
    {
        unlink(__FILE__);
        //rmdir('src');
        unlink('composer.json.dist');
        unlink('dot.htaccess');
        unlink('LICENSE.md');
        unlink('pubvana/config/config.php');
        unlink('pubvana/config/database.php');
    }

    /**
     * Recursive Copy
     *
     * @param string $src
     * @param string $dst
     */
    private static function recursiveCopy($src, $dst)
    {
        mkdir($dst, 0755);
    
        $iterator = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
                            \RecursiveIteratorIterator::SELF_FIRST
                        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                mkdir($dst . '/' . $iterator->getSubPathName());
            } else {
                copy($file, $dst . '/' . $iterator->getSubPathName());
            }
        }
    }
}
