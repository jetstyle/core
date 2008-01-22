<?php

class SchedulerTask
{
  var $scheduler;
  
  function SchedulerTask(&$scheduler)
  {
    $this->scheduler =& $scheduler;
  }

  function Handle()
  {
    // does nothing
    return true; // it will cause removing this task
  }

  function remove()
  {
    $this->scheduler->removeTask($this->id);
  }

  function log($message)
  {
    $this->scheduler->_log($message);
  }

}

?>