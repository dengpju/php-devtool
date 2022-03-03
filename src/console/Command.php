<?php


namespace PHPDTool\console;


use PHPDTool\connection\Db;
use PHPDTool\connection\DbConfig;
use PHPDTool\exception\RuntimeException;

class Command
{
    private $tokens;
    private $parsed;

    /**
     * Command constructor.
     * @param array|null $argv
     */
    public function __construct(array $argv = null)
    {
        $argv = $argv ?? $_SERVER['argv'] ?? [];

        (new HelpDoc())->stdout($argv);

        // strip the application name
        array_shift($argv);

        $this->tokens = $argv;
    }

    public function run()
    {
        var_export($this->tokens);
//        $DBDrive = new Db((new DbConfig()));
//        $DBDrive->connect();
    }

    /**
     * {@inheritdoc}
     */
    protected function parse()
    {
        $parseOptions = true;
        $this->parsed = $this->tokens;
        while (null !== $token = array_shift($this->parsed)) {
            if ($parseOptions && '' == $token) {
                $this->parseArgument($token);
            } elseif ($parseOptions && '--' == $token) {
                $parseOptions = false;
            } elseif ($parseOptions && str_starts_with($token, '--')) {
                $this->parseLongOption($token);
            } elseif ($parseOptions && '-' === $token[0] && '-' !== $token) {
                $this->parseShortOption($token);
            } else {
                $this->parseArgument($token);
            }
        }
    }

    /**
     * Parses a short option.
     * @param string $token
     */
    private function parseShortOption(string $token)
    {
        $name = substr($token, 1);

        if (\strlen($name) > 1) {
            if ($this->definition->hasShortcut($name[0]) && $this->definition->getOptionForShortcut($name[0])->acceptValue()) {
                // an option with a value (with no space)
                $this->addShortOption($name[0], substr($name, 1));
            } else {
                $this->parseShortOptionSet($name);
            }
        } else {
            $this->addShortOption($name, null);
        }
    }

    /**
     * Parses a short option set.
     *
     * @param string $name
     */
    private function parseShortOptionSet(string $name)
    {
        $len = \strlen($name);
        for ($i = 0; $i < $len; ++$i) {
            if (!$this->definition->hasShortcut($name[$i])) {
                $encoding = mb_detect_encoding($name, null, true);
                throw new RuntimeException(sprintf('The "-%s" option does not exist.', false === $encoding ? $name[$i] : mb_substr($name, $i, 1, $encoding)));
            }

            $option = $this->definition->getOptionForShortcut($name[$i]);
            if ($option->acceptValue()) {
                $this->addLongOption($option->getName(), $i === $len - 1 ? null : substr($name, $i + 1));

                break;
            } else {
                $this->addLongOption($option->getName(), null);
            }
        }
    }

    /**
     * Parses a long option.
     * @param string $token
     */
    private function parseLongOption(string $token)
    {
        $name = substr($token, 2);

        if (false !== $pos = strpos($name, '=')) {
            if (0 === \strlen($value = substr($name, $pos + 1))) {
                array_unshift($this->parsed, $value);
            }
            $this->addLongOption(substr($name, 0, $pos), $value);
        } else {
            $this->addLongOption($name, null);
        }
    }

    /**
     * Parses an argument.
     *
     * @param string $token
     */
    private function parseArgument(string $token)
    {
        $c = \count($this->arguments);

        // if input is expecting another argument, add it
        if ($this->definition->hasArgument($c)) {
            $arg = $this->definition->getArgument($c);
            $this->arguments[$arg->getName()] = $arg->isArray() ? [$token] : $token;

            // if last argument isArray(), append token to last argument
        } elseif ($this->definition->hasArgument($c - 1) && $this->definition->getArgument($c - 1)->isArray()) {
            $arg = $this->definition->getArgument($c - 1);
            $this->arguments[$arg->getName()][] = $token;

            // unexpected argument
        } else {
            $all = $this->definition->getArguments();
            $symfonyCommandName = null;
            if (($inputArgument = $all[$key = array_key_first($all)] ?? null) && 'command' === $inputArgument->getName()) {
                $symfonyCommandName = $this->arguments['command'] ?? null;
                unset($all[$key]);
            }

            if (\count($all)) {
                if ($symfonyCommandName) {
                    $message = sprintf('Too many arguments to "%s" command, expected arguments "%s".', $symfonyCommandName, implode('" "', array_keys($all)));
                } else {
                    $message = sprintf('Too many arguments, expected arguments "%s".', implode('" "', array_keys($all)));
                }
            } elseif ($symfonyCommandName) {
                $message = sprintf('No arguments expected for "%s" command, got "%s".', $symfonyCommandName, $token);
            } else {
                $message = sprintf('No arguments expected, got "%s".', $token);
            }

            throw new RuntimeException($message);
        }
    }

    /**
     * Adds a short option value.
     *
     * @param string $shortcut
     * @param $value
     */
    private function addShortOption(string $shortcut, $value)
    {
        if (!$this->definition->hasShortcut($shortcut)) {
            throw new RuntimeException(sprintf('The "-%s" option does not exist.', $shortcut));
        }

        $this->addLongOption($this->definition->getOptionForShortcut($shortcut)->getName(), $value);
    }

    /**
     * Adds a long option value.
     *
     * @param string $name
     * @param $value
     */
    private function addLongOption(string $name, $value)
    {
        if (!$this->definition->hasOption($name)) {
            if (!$this->definition->hasNegation($name)) {
                throw new RuntimeException(sprintf('The "--%s" option does not exist.', $name));
            }

            $optionName = $this->definition->negationToName($name);
            if (null !== $value) {
                throw new RuntimeException(sprintf('The "--%s" option does not accept a value.', $name));
            }
            $this->options[$optionName] = false;

            return;
        }

        $option = $this->definition->getOption($name);

        if (null !== $value && !$option->acceptValue()) {
            throw new RuntimeException(sprintf('The "--%s" option does not accept a value.', $name));
        }

        if (\in_array($value, ['', null], true) && $option->acceptValue() && \count($this->parsed)) {
            // if option accepts an optional or mandatory argument
            // let's see if there is one provided
            $next = array_shift($this->parsed);
            if ((isset($next[0]) && '-' !== $next[0]) || \in_array($next, ['', null], true)) {
                $value = $next;
            } else {
                array_unshift($this->parsed, $next);
            }
        }

        if (null === $value) {
            if ($option->isValueRequired()) {
                throw new RuntimeException(sprintf('The "--%s" option requires a value.', $name));
            }

            if (!$option->isArray() && !$option->isValueOptional()) {
                $value = true;
            }
        }

        if ($option->isArray()) {
            $this->options[$name][] = $value;
        } else {
            $this->options[$name] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getFirstArgument()
    {
        $isOption = false;
        foreach ($this->tokens as $i => $token) {
            if ($token && '-' === $token[0]) {
                if (str_contains($token, '=') || !isset($this->tokens[$i + 1])) {
                    continue;
                }

                // If it's a long option, consider that everything after "--" is the option name.
                // Otherwise, use the last char (if it's a short option set, only the last one can take a value with space separator)
                $name = '-' === $token[1] ? substr($token, 2) : substr($token, -1);
                if (!isset($this->options[$name]) && !$this->definition->hasShortcut($name)) {
                    // noop
                } elseif ((isset($this->options[$name]) || isset($this->options[$name = $this->definition->shortcutToName($name)])) && $this->tokens[$i + 1] === $this->options[$name]) {
                    $isOption = true;
                }

                continue;
            }

            if ($isOption) {
                $isOption = false;
                continue;
            }

            return $token;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function hasParameterOption($values, bool $onlyParams = false)
    {
        $values = (array)$values;

        foreach ($this->tokens as $token) {
            if ($onlyParams && '--' === $token) {
                return false;
            }
            foreach ($values as $value) {
                // Options with values:
                //   For long options, test for '--option=' at beginning
                //   For short options, test for '-o' at beginning
                $leading = str_starts_with($value, '--') ? $value . '=' : $value;
                if ($token === $value || '' !== $leading && str_starts_with($token, $leading)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getParameterOption($values, $default = false, bool $onlyParams = false)
    {
        $values = (array)$values;
        $tokens = $this->tokens;

        while (0 < \count($tokens)) {
            $token = array_shift($tokens);
            if ($onlyParams && '--' === $token) {
                return $default;
            }

            foreach ($values as $value) {
                if ($token === $value) {
                    return array_shift($tokens);
                }
                // Options with values:
                //   For long options, test for '--option=' at beginning
                //   For short options, test for '-o' at beginning
                $leading = str_starts_with($value, '--') ? $value . '=' : $value;
                if ('' !== $leading && str_starts_with($token, $leading)) {
                    return substr($token, \strlen($leading));
                }
            }
        }

        return $default;
    }

    /**
     * Returns a stringified representation of the args passed to the command.
     *
     * @return string
     */
    public function __toString()
    {
        $tokens = array_map(function ($token) {
            if (preg_match('{^(-[^=]+=)(.+)}', $token, $match)) {
                return $match[1] . $this->escapeToken($match[2]);
            }

            if ($token && '-' !== $token[0]) {
                return $this->escapeToken($token);
            }

            return $token;
        }, $this->tokens);

        return implode(' ', $tokens);
    }
}