<?php
/*
    ObjectCache( &$rh ) -- итеративный кэш объектов в пределах запроса
      - $rh -- ссылка на RequestHandler, в котором содержится конфигурация проекта

  ---------
  * &Restore( $object_class, $object_id, $cache_level=0 ) -- вернуть ссылку на объект из кэша. === false, если его там нет
      - $object_class -- строка-псевдокласс объекта, например "page"
      - $object_id    -- идентификатор (желательно численный) объекта, например, "/products/ak74"
      - $cache_level  -- уровень детализации, необходимый для выполнения дальнейших операций
                         хранимый в кэше объект возвращается, только если его cache_level не меньше

  * Store( $object_class, $object_id, $cache_level, &$object, $strength=2 ) -- сохранить ссылку на объект в кэш 
      - $object_class -- строка-псевдокласс объекта, например "page"
      - $object_id    -- идентификатор (желательно численный) объекта, например, "/products/ak74"
      - $cache_level  -- уровень детализации данного объекта. Рекомендуется: 0=id, 1=id+name, 2=id+name+fkeys, 3=*
      - $object       -- сохраняемый объект
      - $strength     -- нужно ли перезаписывать, если уже есть запись в кэше
                          * 0 -- нет
                          * 1 -- только, если запись в кэше имеет меньший уровень детализации
                          * 2 -- только, если запись в кэше имеет меньший или такой же уровень детализации
                          * 3 -- в любом случае

  * Clear( $object_class="", $object_id="" ) -- очистить кэш от объекта/класса объектов/совсем
      - $object_class -- если пустой, то кэш очищается полностью
      - $object_id    -- если пустой, то очищается кэш для всего класса, иначе удаляется только один объект

  * в следующих версиях появится Dump / FromDump -- преобразование кэша из запросового в сеансовый


=============================================================== v.2 (Kuso)
*/

class ObjectCache
{
  var $rh;
  var $data;
  var $levels;

  function ObjectCache( &$rh )
  {
    $this->data = array();
    $this->rh = &$rh;
  }

  // прочитать объект из кэша 
  function &Restore( $object_class, $object_id, $cache_level=0 )
  {
    if (is_array($this->levels[$object_class]))
      if (isset($this->levels[$object_class][$object_id]))
        if ($this->levels[$object_class][$object_id] >= $cache_level)
         return $this->data[$object_class][$object_id];
    return false;
  }

  // сохранить объект в кэше 
  function Store( $object_class, $object_id, $cache_level, &$object, $strength=2 )
  {
    $level=-1;
    if (is_array($this->levels[$object_class]))
      if (isset($this->levels[$object_class][$object_id]))
        if ($this->levels[$object_class][$object_id] >= $cache_level)
         $level = $this->levels[$object_class][$object_id];

    if (($strength==3) || ($level<0) || ($level+1 < $cache_level+$strength))
    {   $this->levels[$object_class][$object_id] = $cache_level;
        $this->data  [$object_class][$object_id] = &$object;
    }
  }

  // очистить кэш от объекта/-ов класса/-ов
  function Clear( $object_class="", $object_id="" )
  {
    if ($object_class && $object_id) $this->levels[$object_class][$object_id] = -2; 
    else
    if ($object_class ) $this->levels[$object_class] = array(); 
    else                $this->levels                = array();
  }

// EOC{ ObjectCache } 
// ForR2-3: Dump, FromDump
}



?>