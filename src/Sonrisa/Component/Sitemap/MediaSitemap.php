<?php
/*
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sonrisa\Component\Sitemap;

use Sonrisa\Component\Sitemap\Exceptions\SitemapException;
use Sonrisa\Component\Sitemap\Items\MediaItem;
use Sonrisa\Component\Sitemap\Validators\SharedValidator;
use Sonrisa\Component\Sitemap\Collections\MediaCollection;

/**
 * Class MediaSitemap
 * @package Sonrisa\Component\Sitemap
 */
class MediaSitemap extends AbstractSitemap implements SitemapInterface
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $link;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var MediaItem
     */
    protected $lastItem;


    /**
     * @param $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param $link
     *
     * @return $this
     */
    public function setLink($link)
    {

        $this->link = SharedValidator::validateLoc($link);

        if(empty($this->link))
        {
            throw new SitemapException('Value for setLink is not a valid URL');
        }

        return $this;
    }

    /**
     * @param $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param  MediaItem $item
     * @return $this
     */
    public function add(MediaItem $item)
    {
        $itemLink = $item->getLink();

        if (!empty($itemLink)) {

            //Check constrains
            $current = $this->current_file_byte_size + $item->getHeaderSize() + $item->getFooterSize();

            //Check if new file is needed or not. ONLY create a new file if the constrains are met.
            if ( ($current <= $this->max_filesize) && ( $this->total_items <= $this->max_items_per_sitemap) ) {
                //add bytes to total
                $this->current_file_byte_size = $item->getItemSize();

                //add item to the item array
                $built = $item->build();
                if (!empty($built)) {
                    $this->items[] = $built;

                    $this->files[$this->total_files] = implode("\n",$this->items);

                    $this->total_items++;
                }

            } else {
                //reset count
                $this->current_file_byte_size = 0;

                //copy items to the files array.
                $this->total_files=$this->total_files+1;
                $this->files[$this->total_files] = implode("\n",$this->items);

                //reset the item count by inserting the first new item
                $this->items = array($item);
                $this->total_items=1;
            }
            $this->lastItem = $item;
        }

        return $this;
    }

    /**
     * @param  MediaCollection $collection
     * @return $this
     */
    public function addCollection(MediaCollection $collection)
    {
        return $this;
    }

    /**
     * @return array
     */
    public function build()
    {
        $output = array();
        if (!empty($this->files)) {
            if (!empty($this->title)) {
                $this->title = "\t<title>{$this->title}</title>\n";
            }

            if (!empty($this->link)) {
                $this->link = "\t<link>{$this->link}</link>\n";
            }

            if (!empty($this->description)) {
                $this->description = "\t<description>{$this->description}</description>\n";
            }

            foreach ($this->files as $file) {
                if ( str_replace(array("\n","\t"),'',$file) != '' ) {
                    $output[] = $this->lastItem->getHeader()."\n".$this->title.$this->link.$this->description.$file."\n".$this->lastItem->getFooter();
                }
            }
        }

        return $output;
    }

}
