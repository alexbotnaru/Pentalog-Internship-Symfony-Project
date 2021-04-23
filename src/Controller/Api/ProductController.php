<?php

namespace App\Controller\API;

use App\Entity\Product;
use App\Form\ProductType;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

    /**
     * @Route("/api/v1/products")
     */

class ProductController extends AbstractController
{
    /**
     * @Route("/", name="search", methods={"GET"})
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function searchProducts(Request $request)
    {
        if($request->query->get('limit') > 100){
            return new Response('Search limit cannot exceed 100 items.', 525);
        }
        $repo = $this->getDoctrine()->getRepository(Product::class);
        return $this->json($repo->filter($request->query->get('category'),
            $request->query->get('name'),
            $request->query->get('limit'),
            $request->query->get('page')));

    }

    /**
     * @Route("/{productCode}", name="api.product.details",requirements={"productCode":"[A][B]\d+"}, methods={"GET"})
     * @param string $productCode
     * @return JsonResponse|Response
     */
    public function getProductByCode(string $productCode)
    {
        $repo=$this->getDoctrine()->getRepository(Product::class);
        $product = $repo->findOneBy(['code'=>$productCode]);
        if(!$product){
            return new Response(null, 404);
        }
        return $this->json($product);
    }

    /**
     * @Route ("/create", name="create",methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function createProduct(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();

        $content = $request->toArray();

        $repo = $this->getDoctrine()->getRepository(Product::class);

        $product = new Product();
        $product->setCode($content['code']);
        $product->setName($content['name']);
        $product->setCategory($content['category']);
        $product->setPrice($content['price']);
        $product->setDescription($content['description']);
        $product->setCreatedAt(new \DateTime(null, new \DateTimeZone('Europe/Athens')));

        if ($repo->count(['code'=> $product->getCode()]) > 0){
            throw new BadRequestException('A product with this code exists already!');
        }
        elseif (strlen($content['code']) == 0){
            throw new BadRequestException('Code cant be blank!');
        }
        elseif (strlen($content['name']) == 0){
            throw new BadRequestException('Name cant be blank!');
        }
        elseif (strlen($content['price']) == 0){
            throw new BadRequestException('Price cant be blank!');
        }
        elseif (strlen($content['category']) == 0){
            throw new BadRequestException('Category cant be blank!');
        }

        $em->persist($product);

        $em->flush();

        return new Response('Product created!');
    }

    /**
     * @Route ("/{productCode}", name="update", requirements={"productCode":"[A][B]\d+"}, methods={"PUT"})
     * @param string $productCode
     * @return Response
     * @throws \Exception
     */
    public function updateProduct(string $productCode, Request $request){
        $data = json_decode($request->getContent(), true);
        $repo = $this->getDoctrine()->getRepository(Product::class);
        $product = $repo->findOneBy(['code' => $productCode]);
        if(!$product){
            return new Response(null, 404);
        }
        $product->setUpdatedAt(new \DateTime(null, new \DateTimeZone('Europe/Athens')));
        $form = $this->createForm(ProductType::class, $product);
        $form->submit($data);
        $em = $this->getDoctrine()->getManager();
        $em->persist($product);
        $em->flush();
        return new Response(null, 200);
    }
}
