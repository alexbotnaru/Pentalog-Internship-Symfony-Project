<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProductRepository;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $category;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $productImage;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt = null;

    /**
     * @ORM\Column(type="integer")
     */
    private $availableAmount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAvailableAmount(): ?int
    {
        return $this->availableAmount;
    }

    public function setAvailableAmount(int $availableAmount): self
    {
        $this->availableAmount = $availableAmount;

        return $this;
    }
    public function readImgPathCSV(): ?string
    {
        return str_replace(["[", "]", "\""], " ", $this->productImage);
    }

    public function getProductImage() {
        return $this->productImage;
    }

    public function readImgPathsArray(): ?array
    {
        return json_decode($this->productImage);
    }


    public function setProductImage(string $paths): self
    {
        $paths = explode(',', $paths);
        $trimmedPaths = [];
        foreach ($paths as $path) {
            $path = ltrim($path);
            $path = rtrim($path);
            array_push($trimmedPaths, $path);
        }
        $this->productImage = json_encode($trimmedPaths);
        return $this;
    }

    public function writeImgPathEgal($paths): self
    {
        $this->productImage= $paths;
        return $this;
    }

    public function writeImgPathsFromArray(array $paths): self
    {
        $this->productImage = json_encode($paths);
        return $this;
    }
}
