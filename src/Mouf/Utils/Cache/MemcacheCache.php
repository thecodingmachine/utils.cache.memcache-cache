<?php
namespace Mouf\Utils\Cache;
use Mouf\Utils\Log\LogInterface;
use Psr\Log\LoggerInterface;
/**
 * This package contains a cache mechanism that relies on Memcache lib.
 * 
 * @Component
 */
class MemcacheCache implements CacheInterface {
	
	/**
	 * The default time to live of elements stored in the session (in seconds).
	 * Please note that if the session is flushed, all the elements of the cache will disapear anyway.
	 * If empty, the time to live will be the time of the session. 
	 *
	 * @Property
	 * @var int
	 */
	public $defaultTimeToLive;
	
	/**
	 * The logger used to trace the cache activity.
	 * Supports both PSR3 compatible logger and old Mouf logger for compatibility reasons.
	 *
	 * @var LoggerInterface|LogInterface
	 */
	public $log;
	
	/**
	 * Memcached Server parameters. Register the host and port for each server, like "127.0.0.1;11211"
	 * This plugin establishes a connection only if it is used by a request.
	 *
	 * @Property
	 * @Compulsory
	 * @var array<string>
	 */
	public $servers;
	
	/**
	 * If the connection cannot be establish, throw an exception if the parameter is check (by default), else nothing
	 *
	 * @Property
	 * @Compulsory
	 * @var bool
	 */
	public $crash = true;
	
	/**
	 * Memcached object to save the connection
	 * 
	 * @var Memcache
	 */
	private $memcacheObject = null;
	
	/**
	 * Save value if it is impossible to establish memcache connection
	 * 
	 * @var bool
	 */
	private $noConnection = false;
	
	/**
	 * Establish the connection to the memcached server
	 * 
	 * @throws Exception If no connection set in the Mouf configuration
	 */
	private function connection() {
		if($this->servers) {
			$this->memcacheObject = new Memcache;
			$noServer = true;
			foreach ($this->servers as $server) {
				$parameters = explode(';', $server);
				if(!isset($parameters[1]))
					$parameters[1] = '11211';
				$this->memcacheObject->addServer($parameters[0], $parameters[1]);
				if($this->memcacheObject->getServerStatus($parameters[0], $parameters[1]) != 0)
					$noServer = false;
			}
			if($noServer) {
				$this->noConnection = true;
				$this->log->error('Memcache Exception, Impossible to establish connection to memcached server');
				if($this->crash)
					throw new Exception('Memcache Exception, Impossible to establish connection to memcached server');
				return false;
			}
			return true;
		}
		throw new Exception("Error no connection set in Memcache cache. Add it in the Mouf interface");
	}
	
	/**
	 * Returns the cached value for the key passed in parameter.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		if(is_null($this->memcacheObject)) {
			if(!$this->connection())
				return false;
		}
		elseif($this->noConnection)
			return false;
			
		return @$this->memcacheObject->get($key);
	}
	
	/**
	 * Sets the value in the cache.
	 *
	 * @param string $key The key of the value to store
	 * @param mixed $value The value to store
	 * @param float $timeToLive The time to live of the cache, in seconds.
	 */
	public function set($key, $value, $timeToLive = null) {
		if(is_null($this->memcacheObject)) {
			if(!$this->connection())
				return false;
		}
		elseif($this->noConnection)
			return false;
		
		$this->memcacheObject->set($key, $value, null, $timeToLive);
	}
	
	/**
	 * Removes the object whose key is $key from the cache.
	 *
	 * @param string $key The key of the object
	 */
	public function purge($key) {
		if(is_null($this->memcacheObject)) {
			if(!$this->connection())
				return false;
		}
		elseif($this->noConnection)
			return false;
		
		$this->memcacheObject->delete($key);
	}
	
	/**
	 * Removes all the objects from the cache.
	 *
	 */
	public function purgeAll() {
		if(is_null($this->memcacheObject)) {
			if(!$this->connection())
				return false;
		}
		elseif($this->noConnection)
			return false;
		
		$this->memcacheObject->flush();
	}
	
}
?>