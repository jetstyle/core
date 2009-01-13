<?php
/**
 * CriticalSection PHP Class
 * =========================
 *
 * This class implements mechanism of critical sections.
 * Critical sections prevents termination of PHP script
 * due to client disconnection or time-out limit when
 * PHP executes some part of code that should not be
 * interrupted.
 *
 * If PHP is running in Safe Mode, critical sections does not
 * prevent script termination by time-out limit. This is
 * restriction of Safe Mode without any workaround.
 *
 * More information:
 * http://www.php.net/manual/en/features.connection-handling.php
 *
 *
 * Tips of usage
 * =============
 *
 * All method should be called statically:
 *
 * CriticalSection::init();
 * CriticalSection::open();     // open critical section
 *
 * // Your critical code is here
 *
 * CriticalSection::close();    // close critical section
 */

class CriticalSection
{

  /**
   * This is initialization static method which should be called at the start of script
   * This initializes a timer which is used for passing correct argument to 
   * set_time_out() function. It is necessary due to PHP does not offer the way to get
   * state of internal counter of execution time and resets it every time the set_time_limit()
   * called, so we have to calculate execution time and pass correct value into set_time_limit().
   * If you do not call ::init() at the start of script - timer will be initialized with
   * first opened critical section. (static method)
   *
   * @access  public
   */
  function init()
  {
    CriticalSection::_startTime();
  }

  /**
   * Get/Set current level of nesting critical sections (static method)
   *
   * @param   integer   New level to be set
   * @return  integer   Current level (if argument specified, it's returned)
   * @access  private
   */
  function _level($newLevel = null)
  {
    static $level;
    if ($newLevel !== null)
      $level = $newLevel;
    return $level;
  }

  /**
   * Get time when script was started (static method)
   *
   * @param   float   Set seconds and microseconds from the UNIX Epoch
   * @return  float   Get time when script was started
   * @access  private
   */
  function _startTime()
  {
    static $startTime = null;
    if ($startTime === null)
      $startTime = (float) (vsprintf('%d.%06d', gettimeofday()));
    return $startTime;
  }

  /**
   * Returns amount of seconds (and microseconds) from start of script 
   * (actually from ::init() call) (static method)
   *
   * @return  float   seconds and microseconds (in a fractional part)
   * @access  private
   */
  function _executionTime()
  {
    return -CriticalSection::_startTime() + (float) (vsprintf('%d.%06d', gettimeofday()));
  }

  /**
   * Checks is critical section currently opened (static method)
   *
   * @return  boolean   If opened TRUE is returned, FALSE is returned otherwise.
   * @access  public
   */
  function isOpened()
  {
    return CriticalSection::_level() > 0;
  }

  /**
   * Opens critical section (static method)
   */
  function open()
  {
    $level = CriticalSection::_level();
    CriticalSection::_startTime();
    if ($level == 0)
    {
      ignore_user_abort(true);
      set_time_limit(0);
    }
    CriticalSection::_level(++$level);
  }

  /**
   * Closes critical section (static method)
   */
  function close()
  {
    $level = CriticalSection::_level();
    if ($level > 0)
    {
      CriticalSection::_level(--$level);
      if ($level == 0)
      {
        ignore_user_abort(false);
        if (($maxExecutionTime = ini_get('max_execution_time')) > 0)
        {
          set_time_limit(max(1, (int) round($maxExecutionTime - CriticalSection::_executionTime())));
        }
      }
    }
  }

}

?>