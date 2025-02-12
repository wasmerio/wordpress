<?php

class Wasmer_Aio_Install_Command
{
    /**
     * All-in-one install command.
     *
     * ## OPTIONS
     *
     * --locale=<locale>
     * : The locale/language for the installation (e.g. `de_DE`).
     *
     * --theme=<theme>
     * : Path to the theme to install.
     */
    public function install($args, $assoc_args)
    {
        WP_CLI::line('Installing theme');
        $command = 'theme install ' . $assoc_args['theme'];
        WP_CLI::line('Running: ' . $command);
        WP_CLI::runcommand($command, ['launch' => false]);

        WP_CLI::line('Installing language');
        $command = 'language core install --activate ' . $assoc_args['locale'];
        WP_CLI::line('Running: ' . $command);
        WP_CLI::runcommand($command, ['launch' => false]);

        WP_CLI::line('Installing theme language');
        $command = 'language theme install --all ' . $assoc_args['locale'];
        WP_CLI::line('Running: ' . $command);
        WP_CLI::runcommand($command, ['launch' => false]);

        WP_CLI::success('All done!');
    }
}

WP_CLI::add_command('wasmer-aio-install', 'Wasmer_Aio_Install_Command');
