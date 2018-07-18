<?php

namespace tool_updatewizard\Exception;

use Exception;
use moodle_exception;

/**
 * Class UpdateWizardException
 *
 * @package tool_updatewizard\Exception
 */
class UpdateWizardException extends Exception
{
    /**
     * @var string The name of the string from error.php to print
     */
    protected $errorCode;

    /**
     * @var mixed Extra words and phrases that might be required in the error string
     */
    protected $a;

    /**
     * Constructor
     * @param Exception                 $previous       [optional] The previous exception used for the exception chaining.
     * @param string                    $errorMessage   The name of the string from error.php to print
     * @param string|object|array|null  $a              Extra words and phrases that might be required in the error string
     *                                                  An object, string or number that can be used within translation strings
     */
    public function __construct(Exception $previous = null, $errorMessage = '', $a = null)
    {
        $message = null === $previous
            ? $this->getUnNonPreviousMessage($errorMessage, $a)
            : $this->getPreviousMessage($previous);

//        $message        = $errorMessage;
//
//        if (null !== $previous) {
//            $message =
//        }
//        $hasErrorString = true;
//        $message        = $errorMessage;
//        $module         = '';
//
//        $stringManager  = get_string_manager();
//
//        if ($stringManager->string_exists($errorMessage, 'tool_updatewizard')) {
//            $module = 'tool_updatewizard';
//        } elseif ($stringManager->string_exists($errorMessage, 'error')) {
//            $module = 'error';
//        } else {
//            $hasErrorString = false;
//        }
//
//        if ($hasErrorString) {
//            $message            = get_string($errorMessage, $module, $a);
//            $this->errorCode    = $errorMessage;
//        }

        parent::__construct($message, 0, $previous);

        //$this->message = $this->clearMessagePrefix($this->getMessage());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getMessage();
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return mixed
     */
    public function getA()
    {
        return $this->a;
    }

    /**
     * @param Exception $previous
     *
     * @return string
     */
    protected function getPreviousMessage(Exception $previous)
    {
        if ($previous instanceof UpdateWizardException) {
            return $this->getPreviousUpdateWizardMessage($previous);
        }

        if ($previous instanceof moodle_exception) {
            return $this->getPreviousMoodleMessage($previous);
        }

        return $this->getUnNonPreviousMessage($previous->getMessage());
    }

    /**
     * @param UpdateWizardException $previous
     *
     * @return string
     */
    protected function getPreviousUpdateWizardMessage(UpdateWizardException $previous)
    {
        $this->errorCode    = $previous->getErrorCode();
        $this->a            = $previous->getA();

        return $previous;
    }

    /**
     * @param moodle_exception $previous
     *
     * @return string
     */
    protected function getPreviousMoodleMessage(moodle_exception $previous)
    {
        $this->errorCode    = $previous->errorcode;
        $this->a            = $previous->a;

        $message            = $previous->getMessage();

        return $this->clearMessagePrefix($message);
    }

    /**
     * @param string                    $errorMessage
     * @param string|object|array|null  $a
     *
     * @return string
     */
    protected function getUnNonPreviousMessage($errorMessage, $a = null)
    {
        $hasErrorString = true;
        $message        = $errorMessage;
        $module         = '';

        $stringManager  = get_string_manager();

        if ($stringManager->string_exists($errorMessage, 'tool_updatewizard')) {
            $module = 'tool_updatewizard';
        } elseif ($stringManager->string_exists($errorMessage, 'error')) {
            $module = 'error';
        } else {
            $hasErrorString = false;
        }

        if ($hasErrorString) {
            $message            = get_string($errorMessage, $module, $a);
            $this->errorCode    = $errorMessage;
        }

        if ('' === $message) {
            $message = '???';
        }

        return $message;
    }

    /**
     * @param string $message
     *
     * @return string
     */
    protected function clearMessagePrefix($message)
    {
        $pattern        = '/(.+\/)?(.*)/';
        $replacement    = '$2';

        return preg_replace($pattern, $replacement, $message);
    }
}
