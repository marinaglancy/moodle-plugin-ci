<?php

/*
 * This file is part of the Moodle Plugin CI package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moodlerooms\MoodlePluginCI\Command;

use Moodlerooms\MoodlePluginCI\Bridge\MoodlePlugin;
use Moodlerooms\MoodlePluginCI\Process\Execute;
use Moodlerooms\MoodlePluginCI\Validate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Run CSS Lint on a plugin.
 *
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class CSSLintCommand extends Command
{
    /**
     * @var Execute
     */
    public $execute;

    protected function configure()
    {
        // Install Command sets this in Travis CI.
        $plugin = getenv('PLUGIN_DIR') !== false ? getenv('PLUGIN_DIR') : null;
        $mode   = getenv('PLUGIN_DIR') !== false ? InputArgument::OPTIONAL : InputArgument::REQUIRED;

        $this->setName('csslint')
            ->setDescription('Run CSSLint on a plugin')
            ->addArgument('plugin', $mode, 'Path to the plugin', $plugin);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->execute = $this->execute ?: new Execute($output, $this->getHelper('process'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $validate  = new Validate();
        $pluginDir = realpath($validate->directory($input->getArgument('plugin')));
        $plugin    = new MoodlePlugin($pluginDir);

        $finder = new Finder();
        $finder->name('*.css')->notName('*-min.css');

        $files = $plugin->getRelativeFiles($finder);

        if (empty($files)) {
            $output->writeln('<error>Failed to find any files to process.</error>');

            return 0;
        }

        $output->writeln(sprintf('<bg=green;fg=white;> RUN </> <fg=blue>CSSLint on %s</>', $plugin->getComponent()));

        $process = $this->execute->passThrough('csslint '.implode(' ', $files), $plugin->directory);

        return $process->isSuccessful() ? 0 : 1;
    }
}
