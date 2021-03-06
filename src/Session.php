<?php
namespace Minphp\Session;

use SessionHandlerInterface;
use Minphp\Session\Handlers\NativeHandler;
use LogicException;

/**
 * Session management library.
 */
class Session
{
    /**
     * @var \SessionHandlerInterface The session handler
     */
    protected $handler;
    /**
     * @var bool Whether ot not the session has started
     */
    protected $started = false;

    /**
     * Initialize the Session
     *
     * @param \SessionHandlerInterface $handler The session handler
     */
    public function __construct(SessionHandlerInterface $handler = null, array $options = [])
    {
        session_register_shutdown();

        $this->setOptions($options);

        if (!$this->handler) {
            $this->handler = $handler ?: new NativeHandler();
            session_set_save_handler($this->handler, false);
        }
    }

    /**
     * Sets session ini variables
     *
     * @param array $options Options to set
     * @see http://php.net/session.configuration
     */
    public function setOptions(array $options)
    {
        $supportedOptions = ['save_path', 'name', 'save_handler',
            'gc_probability', 'gc_divisor', 'gc_maxlifetime', 'serialize_handler',
            'cookie_lifetime', 'cookie_path', 'cookie_domain', 'cookie_secure',
            'cookie_httponly', 'use_strict_mode', 'use_cookies', 'use_only_cookies',
            'referer_check', 'entropy_file', 'entropy_length', 'cache_limiter',
            'cache_expire', 'use_trans_sid', 'hash_function', 'hash_bits_per_character',
            'upload_progress.enabled', 'upload_progress.cleanup', 'upload_progress.prefix',
            'upload_progress.name', 'upload_progress.freq', 'upload_progress.min_freq',
            'lazy_write'
        ];

        foreach ($options as $key => $value) {
            if (in_array($key, $supportedOptions)) {
                ini_set('session.' . $key, $value);
            }
        }
    }

    /**
     * Start the session
     *
     * @return bool True if the session has started
     */
    public function start()
    {
        if (!$this->hasStarted()) {
            $this->started = true;
            session_start();
        }

        return true;
    }

    /**
     * Return whether the session has started or not
     *
     * @return bool True if the session has started
     */
    public function hasStarted()
    {
        return $this->started;
    }

    /**
     * Saves and closes the session
     */
    public function save()
    {
        session_write_close();
        $this->started = false;
    }

    /**
     * Regenerates the session
     *
     * @param bool $destroy True to destroy the current session
     * @param int $lifetime The lifetime of the session cookie in seconds
     * @return bool True if regenerated, false otherwise
     */
    public function regenerate($destroy = false, $lifetime = null)
    {
        if (!$this->hasStarted()) {
            return false;
        }

        if (null !== $lifetime) {
            ini_set('session.cookie_lifetime', $lifetime);
        }

        return session_regenerate_id($destroy);
    }

    /**
     * Return the session ID
     *
     * @return string The session ID
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * Sets the session ID
     *
     * @param string $sessionId The ID to set
     * @throws \LogicException
     */
    public function setId($sessionId)
    {
        if ($this->hasStarted()) {
            throw new LogicException('Session already started, can not change ID.');
        }
        session_id($sessionId);
    }

    /**
     * Return the session name
     *
     * @return string The session name
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * Sets the session name
     *
     * @param string $name The session name
     */
    public function setName($name)
    {
        if ($this->hasStarted()) {
            throw new LogicException('Session already started, can not change name.');
        }
        session_name($name);
    }

    /**
     * Read session information for the given name
     *
     * @param string $name The name of the item to read
     * @return mixed The value stored in $name of the session, or an empty string.
     */
    public function read($name)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
        return '';
    }

    /**
     * Writes the given session information to the given name
     *
     * @param string $name The name to write to
     * @param mixed $value The value to write
     */
    public function write($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Unsets the value of a given session variable, or the entire session of
     * all values
     *
     * @param string $name The name to unset
     */
    public function clear($name = null)
    {
        if ($name) {
            unset($_SESSION[$name]);
        } else {
            $_SESSION = [];
        }
    }
}
