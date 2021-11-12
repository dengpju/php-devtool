<?php


namespace PHPDTool\console;


class HelpDoc
{
    private $logo = <<<LOGO
See https://github.com/dengpju/php-devtool for details
    ____  ____  ___
   / __ \/ __ \/__/
  / /_/ / /_/ /  /
 / .___/ .___/  /
/_/   /_/   /__/
ppi version 1.8.0 2018-12-03 10:31:16
LOGO;

    private $readme = <<<README
Usage:
  command [options] [arguments]

Options:
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
      --profile                  Display timing and memory usage information
      --no-plugins               Whether to disable plugins.
  -d, --working-dir=WORKING-DIR  If specified, use the given directory as working directory.
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
README;

    public function stdout(array $argv)
    {
        if (empty($argv[1])) {
            echo $this->logo . $this->readme . PHP_EOL;
            exit(1);
        }
    }
}