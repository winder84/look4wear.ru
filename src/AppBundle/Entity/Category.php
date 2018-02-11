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
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="seoTitle", type="string", length=255, nullable=true)
     */
    private $seoTitle;

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
    private $isActive = true;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parentCategory")
     */
    private $childrenCategories;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="childrenCategories")
     * @ORM\JoinColumn(name="parentId", referencedColumnName="id")
     */
    private $parentCategory;

    /**
     * @var json_encode
     *
     * @ORM\Column(name="data", type="json_array", nullable=true)
     */
    private $data;

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
     * Add childrenCategory
     *
     * @param \AppBundle\Entity\Category $childrenCategory
     *
     * @return Category
     */
    public function addChildrenCategory(\AppBundle\Entity\Category $childrenCategory)
    {
        $this->childrenCategories[] = $childrenCategory;

        return $this;
    }

    /**
     * Remove childrenCategory
     *
     * @param \AppBundle\Entity\Category $childrenCategory
     */
    public function removeChildrenCategory(\AppBundle\Entity\Category $childrenCategory)
    {
        $this->childrenCategories->removeElement($childrenCategory);
    }

    /**
     * Get childrenCategories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildrenCategories()
    {
        return $this->childrenCategories;
    }

    /**
     * Set parentCategory
     *
     * @param \AppBundle\Entity\Category $parentCategory
     *
     * @return Category
     */
    public function setParentCategory(\AppBundle\Entity\Category $parentCategory = null)
    {
        $this->parentCategory = $parentCategory;

        return $this;
    }

    /**
     * Get parentCategory
     *
     * @return \AppBundle\Entity\Category
     */
    public function getParentCategory()
    {
        return $this->parentCategory;
    }

    /**
     * Set seoTitle
     *
     * @param string $seoTitle
     *
     * @return Category
     */
    public function setSeoTitle($seoTitle)
    {
        $this->seoTitle = $seoTitle;

        return $this;
    }

    /**
     * Get seoTitle
     *
     * @return string
     */
    public function getSeoTitle()
    {
        return $this->seoTitle;
    }

    /**
     * Set data
     *
     * @param array $data
     *
     * @return Category
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
