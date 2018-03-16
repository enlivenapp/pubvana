<?php
namespace Enlivenapp\Pubvana;

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

        // Run composer update
        self::composerUpdate();

        // install translations
        self::installTranslations();

        // Delete unneeded files
        self::deleteSelf();

        // Show message
        self::showMessage($event);
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
        $io->write('<info>More Information: https://pubvana.org</info>');
        $io->write('==================================================');
        $io->write('<info>Developers: Other third party libraries are available; type:</info>');
        $io->write('$ cd <pubvana_project_folder>');
        $io->write('$ php bin/install.php');
        $io->write('<info>The above command will show the help message.</info>');
        $io->write('See <https://github.com/enlivenapp/pubvana> for  more details');
        $io->write('==================================================');
    }


    private static function deleteSelf()
    {
        unlink(__FILE__);
        //rmdir('application');
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
