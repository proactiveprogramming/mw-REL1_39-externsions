<?php
/**
 * OpenGraph
 *
 * PHP version 5
 *
 * @category Class
 * @package  Swagger\Client
 * @author   http://github.com/swagger-api/swagger-codegen
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Licene v2
 * @link     https://github.com/swagger-api/swagger-codegen
 */

/**
 * discussion
 *
 * No descripton provided (generated by Swagger Codegen https://github.com/swagger-api/swagger-codegen)
 *
 * OpenAPI spec version: 0.1.0-SNAPSHOT
 * 
 * Generated by: https://github.com/swagger-api/swagger-codegen.git
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen
 * Do not edit the class manually.
 */

namespace Swagger\Client\Discussion\Models;

use \ArrayAccess;

/**
 * OpenGraph Class Doc Comment
 *
 * @category    Class */
/** 
 * @package     Swagger\Client
 * @author      http://github.com/swagger-api/swagger-codegen
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache Licene v2
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class OpenGraph implements ArrayAccess
{
    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'OpenGraph';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = array(
        'id' => 'int',
        'site_id' => 'int',
        'url' => 'string',
        'site_name' => 'string',
        'title' => 'string',
        'type' => 'string',
        'image_url' => 'string',
        'description' => 'string',
        'original_url' => 'string',
        'video_url' => 'string',
        'video_secure_url' => 'string',
        'video_type' => 'string',
        'video_height' => 'int',
        'video_width' => 'int',
        'image_height' => 'int',
        'image_width' => 'int',
        'date_retrieved' => '\Swagger\Client\Discussion\Models\Instant'
    );

    public static function swaggerTypes()
    {
        return self::$swaggerTypes;
    }

    /**
     * Array of attributes where the key is the local name, and the value is the original name
     * @var string[]
     */
    protected static $attributeMap = array(
        'id' => 'id',
        'site_id' => 'siteId',
        'url' => 'url',
        'site_name' => 'siteName',
        'title' => 'title',
        'type' => 'type',
        'image_url' => 'imageUrl',
        'description' => 'description',
        'original_url' => 'originalUrl',
        'video_url' => 'videoUrl',
        'video_secure_url' => 'videoSecureUrl',
        'video_type' => 'videoType',
        'video_height' => 'videoHeight',
        'video_width' => 'videoWidth',
        'image_height' => 'imageHeight',
        'image_width' => 'imageWidth',
        'date_retrieved' => 'dateRetrieved'
    );

    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = array(
        'id' => 'setId',
        'site_id' => 'setSiteId',
        'url' => 'setUrl',
        'site_name' => 'setSiteName',
        'title' => 'setTitle',
        'type' => 'setType',
        'image_url' => 'setImageUrl',
        'description' => 'setDescription',
        'original_url' => 'setOriginalUrl',
        'video_url' => 'setVideoUrl',
        'video_secure_url' => 'setVideoSecureUrl',
        'video_type' => 'setVideoType',
        'video_height' => 'setVideoHeight',
        'video_width' => 'setVideoWidth',
        'image_height' => 'setImageHeight',
        'image_width' => 'setImageWidth',
        'date_retrieved' => 'setDateRetrieved'
    );

    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = array(
        'id' => 'getId',
        'site_id' => 'getSiteId',
        'url' => 'getUrl',
        'site_name' => 'getSiteName',
        'title' => 'getTitle',
        'type' => 'getType',
        'image_url' => 'getImageUrl',
        'description' => 'getDescription',
        'original_url' => 'getOriginalUrl',
        'video_url' => 'getVideoUrl',
        'video_secure_url' => 'getVideoSecureUrl',
        'video_type' => 'getVideoType',
        'video_height' => 'getVideoHeight',
        'video_width' => 'getVideoWidth',
        'image_height' => 'getImageHeight',
        'image_width' => 'getImageWidth',
        'date_retrieved' => 'getDateRetrieved'
    );

    public static function getters()
    {
        return self::$getters;
    }

    

    

    /**
     * Associative array for storing property values
     * @var mixed[]
     */
    protected $container = array();

    /**
     * Constructor
     * @param mixed[] $data Associated array of property value initalizing the model
     */
    public function __construct(array $data = null)
    {
        $this->container['id'] = isset($data['id']) ? $data['id'] : null;
        $this->container['site_id'] = isset($data['site_id']) ? $data['site_id'] : null;
        $this->container['url'] = isset($data['url']) ? $data['url'] : null;
        $this->container['site_name'] = isset($data['site_name']) ? $data['site_name'] : null;
        $this->container['title'] = isset($data['title']) ? $data['title'] : null;
        $this->container['type'] = isset($data['type']) ? $data['type'] : null;
        $this->container['image_url'] = isset($data['image_url']) ? $data['image_url'] : null;
        $this->container['description'] = isset($data['description']) ? $data['description'] : null;
        $this->container['original_url'] = isset($data['original_url']) ? $data['original_url'] : null;
        $this->container['video_url'] = isset($data['video_url']) ? $data['video_url'] : null;
        $this->container['video_secure_url'] = isset($data['video_secure_url']) ? $data['video_secure_url'] : null;
        $this->container['video_type'] = isset($data['video_type']) ? $data['video_type'] : null;
        $this->container['video_height'] = isset($data['video_height']) ? $data['video_height'] : null;
        $this->container['video_width'] = isset($data['video_width']) ? $data['video_width'] : null;
        $this->container['image_height'] = isset($data['image_height']) ? $data['image_height'] : null;
        $this->container['image_width'] = isset($data['image_width']) ? $data['image_width'] : null;
        $this->container['date_retrieved'] = isset($data['date_retrieved']) ? $data['date_retrieved'] : null;
    }

    /**
     * show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalid_properties = array();
        return $invalid_properties;
    }

    /**
     * validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properteis are valid
     */
    public function valid()
    {
        return true;
    }


    /**
     * Gets id
     * @return int
     */
    public function getId()
    {
        return $this->container['id'];
    }

    /**
     * Sets id
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->container['id'] = $id;

        return $this;
    }

    /**
     * Gets site_id
     * @return int
     */
    public function getSiteId()
    {
        return $this->container['site_id'];
    }

    /**
     * Sets site_id
     * @param int $site_id
     * @return $this
     */
    public function setSiteId($site_id)
    {
        $this->container['site_id'] = $site_id;

        return $this;
    }

    /**
     * Gets url
     * @return string
     */
    public function getUrl()
    {
        return $this->container['url'];
    }

    /**
     * Sets url
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->container['url'] = $url;

        return $this;
    }

    /**
     * Gets site_name
     * @return string
     */
    public function getSiteName()
    {
        return $this->container['site_name'];
    }

    /**
     * Sets site_name
     * @param string $site_name
     * @return $this
     */
    public function setSiteName($site_name)
    {
        $this->container['site_name'] = $site_name;

        return $this;
    }

    /**
     * Gets title
     * @return string
     */
    public function getTitle()
    {
        return $this->container['title'];
    }

    /**
     * Sets title
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->container['title'] = $title;

        return $this;
    }

    /**
     * Gets type
     * @return string
     */
    public function getType()
    {
        return $this->container['type'];
    }

    /**
     * Sets type
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->container['type'] = $type;

        return $this;
    }

    /**
     * Gets image_url
     * @return string
     */
    public function getImageUrl()
    {
        return $this->container['image_url'];
    }

    /**
     * Sets image_url
     * @param string $image_url
     * @return $this
     */
    public function setImageUrl($image_url)
    {
        $this->container['image_url'] = $image_url;

        return $this;
    }

    /**
     * Gets description
     * @return string
     */
    public function getDescription()
    {
        return $this->container['description'];
    }

    /**
     * Sets description
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->container['description'] = $description;

        return $this;
    }

    /**
     * Gets original_url
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->container['original_url'];
    }

    /**
     * Sets original_url
     * @param string $original_url
     * @return $this
     */
    public function setOriginalUrl($original_url)
    {
        $this->container['original_url'] = $original_url;

        return $this;
    }

    /**
     * Gets video_url
     * @return string
     */
    public function getVideoUrl()
    {
        return $this->container['video_url'];
    }

    /**
     * Sets video_url
     * @param string $video_url
     * @return $this
     */
    public function setVideoUrl($video_url)
    {
        $this->container['video_url'] = $video_url;

        return $this;
    }

    /**
     * Gets video_secure_url
     * @return string
     */
    public function getVideoSecureUrl()
    {
        return $this->container['video_secure_url'];
    }

    /**
     * Sets video_secure_url
     * @param string $video_secure_url
     * @return $this
     */
    public function setVideoSecureUrl($video_secure_url)
    {
        $this->container['video_secure_url'] = $video_secure_url;

        return $this;
    }

    /**
     * Gets video_type
     * @return string
     */
    public function getVideoType()
    {
        return $this->container['video_type'];
    }

    /**
     * Sets video_type
     * @param string $video_type
     * @return $this
     */
    public function setVideoType($video_type)
    {
        $this->container['video_type'] = $video_type;

        return $this;
    }

    /**
     * Gets video_height
     * @return int
     */
    public function getVideoHeight()
    {
        return $this->container['video_height'];
    }

    /**
     * Sets video_height
     * @param int $video_height
     * @return $this
     */
    public function setVideoHeight($video_height)
    {
        $this->container['video_height'] = $video_height;

        return $this;
    }

    /**
     * Gets video_width
     * @return int
     */
    public function getVideoWidth()
    {
        return $this->container['video_width'];
    }

    /**
     * Sets video_width
     * @param int $video_width
     * @return $this
     */
    public function setVideoWidth($video_width)
    {
        $this->container['video_width'] = $video_width;

        return $this;
    }

    /**
     * Gets image_height
     * @return int
     */
    public function getImageHeight()
    {
        return $this->container['image_height'];
    }

    /**
     * Sets image_height
     * @param int $image_height
     * @return $this
     */
    public function setImageHeight($image_height)
    {
        $this->container['image_height'] = $image_height;

        return $this;
    }

    /**
     * Gets image_width
     * @return int
     */
    public function getImageWidth()
    {
        return $this->container['image_width'];
    }

    /**
     * Sets image_width
     * @param int $image_width
     * @return $this
     */
    public function setImageWidth($image_width)
    {
        $this->container['image_width'] = $image_width;

        return $this;
    }

    /**
     * Gets date_retrieved
     * @return \Swagger\Client\Discussion\Models\Instant
     */
    public function getDateRetrieved()
    {
        return $this->container['date_retrieved'];
    }

    /**
     * Sets date_retrieved
     * @param \Swagger\Client\Discussion\Models\Instant $date_retrieved
     * @return $this
     */
    public function setDateRetrieved($date_retrieved)
    {
        $this->container['date_retrieved'] = $date_retrieved;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     * @param  integer $offset Offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     * @param  integer $offset Offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     * @param  integer $offset Offset
     * @param  mixed   $value  Value to be set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     * @param  integer $offset Offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Gets the string presentation of the object
     * @return string
     */
    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) { // use JSON pretty print
            return json_encode(\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this), JSON_PRETTY_PRINT);
        }

        return json_encode(\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this));
    }
}

