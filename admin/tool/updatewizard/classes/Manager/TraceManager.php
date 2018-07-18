<?php

namespace tool_updatewizard\Manager;

use combined_progress_trace;
use progress_trace_buffer;
//use error_log_progress_trace;
//use html_list_progress_trace;
//use text_progress_trace;
use null_progress_trace;
use progress_trace;

/**
 * Class TraceManager
 */
class TraceManager extends combined_progress_trace
{
    /**
     * combined_progress_trace_manager constructor.
     */
    public function __construct()
    {
        parent::__construct([]);

        $this->addTrace(new null_progress_trace());
    }

    /**
     * @param progress_trace $trace
     *
     * @return string
     */
    public function addTrace(progress_trace $trace)
    {
        //$passThrough = array_key_exists('XDEBUG_SESSION', $_COOKIE);
        $passThrough = false;

        $traceName = get_class($trace);

        $progressTraceBuffer = new progress_trace_buffer($trace, $passThrough);

        $this->traces[$traceName] = $progressTraceBuffer;

        return $traceName;
    }

    /**
     * @param $traceName
     *
     * @return progress_trace_buffer
     */
    public function getTrace($traceName)
    {
        return
            array_key_exists($traceName, $this->traces)
            ? $this->traces[$traceName]
            : $this->traces['null_progress_trace'];
    }

    /**
     * @param $traceName
     *
     * @return string
     */
    public function getTraceBuffer($traceName)
    {
        $progressTraceBuffer = $this->getTrace($traceName);
        
        return $progressTraceBuffer->get_buffer();
    }
}
