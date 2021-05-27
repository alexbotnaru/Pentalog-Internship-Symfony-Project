<?php

namespace App\Controller;

use App\Entity\Image;
use App\Exceptions\InvalidLimitException;
use App\Exceptions\InvalidPageException;
use App\Form\ImageType;
use App\ImageSearchCriteria;
use App\Repository\ImageRepository;
use App\Repository\ProductRepository;
use DateTime;
use DateTimeZone;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/image/gallery")
 */
class ImageController extends AbstractController
{
    /**
     * @Route("/", name="image_index", methods={"GET"})
     * @param ImageRepository $imageRepository
     * @param Request $request
     * @return Response
     */
    public function index(ImageRepository $imageRepository,Request $request): Response
    {

        $tag = $request->query->get('search');
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);
        if ($limit > 120) {
            throw new BadRequestHttpException("400");
        }

        try {
            $searchImage = new ImageSearchCriteria($tag, $page, $limit);
        } catch (Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $length = $imageRepository->countTotal($searchImage);
        if ($page > ceil($length / $limit) && $length / $limit !== 0) {
            throw new BadRequestHttpException("Page limit exceed");
        }

        return $this->render('admin/image/index.html.twig', [
            'images' => $imageRepository->search($searchImage),
            'length' => $length,
            'limit' => $searchImage->getLimit()
        ]);
    }

    /**
     * @Route("/fragment", name="gallery_fragment", methods={"GET"})
     * @param ImageRepository $imageRepository
     * @return Response
     */
    public function temp(ImageRepository $imageRepository): Response
    {
        return $this->render('image/fragment.html.twig', [
            'images' => $imageRepository->findAll()
        ]);
    }

    private function uploadImageWithSecureName($form, $slugger): string
    {
        $imageFile = $form->get('path')->getData();
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                try {
            $gallery = $this->getParameter('gallery_path');
            $imageFile->move(
                $gallery,
                $newFilename
            );
        } catch (FileException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
                return $newFilename;
    }

    private function checkTags(Image $image): array
    {
        $errors = [];
        foreach ($image->getTagsArray() as $tag) {
            if (mb_strlen($tag) > 25 || mb_strlen($tag) < 2) {
                $errors['tagLen'] = "The length of each tag must be from 2 to 25 characters";
            }
            if (preg_match('/[^a-zа-я0-9]/', $tag)) {
                $errors['tagMatch'] = "The tags must contain only characters and numbers ";
            }
        }
        return $errors;
    }

    /**
     * @Route("/new", name="image_new", methods={"GET","POST"})
     * @param Request $request
     * @param SluggerInterface $slugger
     * @return Response
     */
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->checkTags($image);

            if (!empty($errors)) {
                return $this->render('admin/image/new.html.twig', [
                    'errors' => $errors,
                    'image' => $image,
                    'form' => $form->createView(),
                ]);
            }
            $image->setTagsFromArray($image->getTagsArray());

            $image->setPath($this->uploadImageWithSecureName($form, $slugger));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($image);
            $entityManager->flush();

            return $this->redirectToRoute('image_index');
        }

        return $this->render('image/new.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="image_show", methods={"GET"})
     * @param Image $image
     * @return Response
     */
    public function show(Image $image): Response
    {
        return $this->render('image/show.html.twig', [
            'image' => $image,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="image_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Image $image
     * @param SluggerInterface $slugger
     * @return Response
     */
    public function edit(Request $request, Image $image, SluggerInterface $slugger): Response
    {
        $origPath = $image->getPath();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->checkTags($image);
            if (!empty($errors)) {
                return $this->render('admin/image/edit.html.twig', [
                    'errors' => $errors,
                    'image' => $image,
                    'form' => $form->createView(),
                ]);
            }

            if ($image->getPath() === '% & # { } \\ / ! $ \' \" : < > @  * ? + ` | =') {
                $image->setPath($origPath);
            } else {

                $image->setPath($this->uploadImageWithSecureName($form, $slugger));
            }
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('image_index');
        }
        return $this->render('image/edit.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="image_delete", methods={"POST"})
     * @param Request $request
     * @param Image $image
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function delete(Request $request, Image $image, ProductRepository $productRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $this->deleteImageFromProducts($image, $productRepository);
            $filesystem = new Filesystem();
            $gallery = $this->getParameter('');
            $filesystem->remove($gallery .$image->getPath());
            $entityManager->remove($image);
            $entityManager->flush();
        }

        return $this->redirectToRoute('image_index');
    }

    public function deleteImageFromProducts(Image $image, ProductRepository $productRepository) {
        $products = $productRepository->findByImage($image);
        foreach ($products as $product) {
            $paths = $product->readImgPathsArray();
            array_splice($paths, array_search($image->getPath(), $paths), 1);
            (count($paths) === 0) ? $product->writeImgPathsFromArray(["250x200.png"]) : $product->writeImgPathsFromArray($paths);
            try {
                $date = new DateTime(null, new DateTimeZone('Europe/Athens'));
            } catch (Exception $e) {
            }
            $product->setUpdatedAt($date);
            $productRepository->updateImgPath($product);
        }
    }
}
