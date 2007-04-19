<?php

class Scheduler
{
  var $rh;

  /**
   * Scheduler's class constructor
   */
  function Scheduler(&$rh)
  {
    $this->rh =& $rh;
  }

  /**
   * Starts processing of queued tasks (main method)
   */
  function run()
  {
    $res = $this->rh->db->execute('
      SELECT
        *, UNIX_TIMESTAMP(`start`) AS `start_unixtime`, UNIX_TIMESTAMP(`end`) AS `end_unixtime`, UNIX_TIMESTAMP(`last`) AS `last_unixtime`
      FROM
        `'.$this->rh->project_name.'_scheduler_tasks`
      WHERE
        (`start` <= NOW() OR `start` IS NULL) AND (`end` >= NOW() OR `end` IS NULL)
      ORDER BY `priority` DESC');
    while (!$res->EOF)
    {
      $saved_missed_OK = $this->rh->missed_OK;
      $this->rh->UseClass($res->fields['class']);
      $this->rh->missed_OK = $saved_missed_OK;
      if (class_exists($res->fields['class'])) // if required task class is exists
      {
        $class = $res->fields['class'];
        $task =& new $class($this);
        if (method_exists($task, 'Handle')) // if method Handle is defined
        {
          foreach ($res->fields as $k => $v)
          {
            $task->$k = $v;
          }
          if ($task->Handle() !== false)
          {
            $this->removeTask($task->id);
          }
          else
          {
            $this->rh->db->execute('UPDATE `'.$this->rh->project_name.'_scheduler_tasks` SET `last` = NOW() WHERE `id` = '.$task->id);
          }
        }
        else // if method Handle is not defined - remove this task
        {
          $this->removeTask($res->fields['id']);
          $this->_log('Class '.$res->fields['class'].' which used to handle task "'.$res->fields['title'].'" (#'.$res->fields['id'].') does not defines method Handle(), the task has been removed');
        }
      }
      else // if required class is absent - remove this task
      {
        $this->removeTask($res->fields['id']);
        $this->_log('Task "'.$res->fields['title'].'" (#'.$res->fields['id'].') was referenced to non-existing class '.$res->fields['class'].' and has been deleted');
      }
      $res->MoveNext();
    }
  }

  /**
   * Creates new task in queue
   *
   * @param   string    Task title
   * @param   string    Name of task class which should be child of SchedulerTask class
   * @param   int       UNIX-TimeStamp since task will be scheduled
   * @param   int       UNIX-TimeStamp till task will be scheduled
   * @param   int       Priority of task (relative), max = 255
   */
  function createTask($title, $class, $start = null, $end = null, $priority = 0)
  {
    $this->rh->db->execute('
      INSERT INTO
        `'.$this->rh->project_name.'_scheduler_tasks`
      SET
        `title` = '.$this->rh->db->quote($title).',
        `class` = '.$this->rh->db->quote($class).',
        `start` = '.(($begin != null) ? 'FROM_UNIXTIME('.$start.')' : 'NULL').',
        `end` = '.(($end != null) ? 'FROM_UNIXTIME('.$end.')' : 'NULL').',
        `last` = NULL,
        `priority` = '.(int) $priority);
  }

  /**
   * Removes task from queue
   *
   * @param   int       Task ID
   */
  function removeTask($id)
  {
    $this->rh->db->execute('DELETE FROM `'.$this->rh->project_name.'_scheduler_tasks` WHERE `id` = '.(int) $id);
  }

  /**
   * Adds record into scheduler's log table
   *
   * @param   string    Message to log
   * @param   int       Level of importance (relative), 0 is default.
   *                    Higher values correspond to higher importance.
   */
  function _log($message, $level = 0)
  {
    $this->rh->db->execute('INSERT INTO `'.$this->rh->project_name.'_scheduler_log` SET `when` = NOW(), `message` = '.$this->rh->db->quote($message).', `level` = '.(int) $level);
  }

}

?>