<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Category
 *
 * @ORM\Table(name="category")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CategoryRepository")
 */
class Category
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", length=255, unique=true)
     */
    private $alias;

    /**
     * @var string
     *
     * @ORM\Column(name="searchString", type="string", length=255)
     */
    private $searchString;

    /**
     * @var array
     *
     * @ORM\Column(name="excludeWords", type="string", nullable=true)
     */
    private $excludeWords;

    /**
     * @var array
     *
     * @ORM\Column(name="keywords", type="string", nullable=true)
     */
    private $keywords;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isActive", type="boolean", nullable=true, options={"default" = true})
     */
    private $isActive = false;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Category")
     * @ORM\JoinColumn(name="parentId", referencedColumnName="id", nullable=true, unique=false)
     */
    private $category;

    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Category
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Category
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set alias
     *
     * @param string $alias
     *
     * @return Category
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set searchString
     *
     * @param string $searchString
     *
     * @return Category
     */
    public function setSearchString($searchString)
    {
        $this->searchString = $searchString;

        return $this;
    }

    /**
     * Get searchString
     *
     * @return string
     */
    public function getSearchString()
    {
        return $this->searchString;
    }

    /**
     * Set excludeWords
     *
     * @param string $excludeWords
     *
     * @return Category
     */
    public function setExcludeWords($excludeWords)
    {
        $this->excludeWords = $excludeWords;

        return $this;
    }

    /**
     * Get excludeWords
     *
     * @return string
     */
    public function getExcludeWords()
    {
        return $this->excludeWords;
    }

    /**
     * Set keywords
     *
     * @param string $keywords
     *
     * @return Category
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Get keywords
     *
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Category
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set category
     *
     * @param \AppBundle\Entity\Category $category
     *
     * @return Category
     */
    public function setCategory(\AppBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \AppBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }
}
