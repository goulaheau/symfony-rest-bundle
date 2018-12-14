<?php

namespace Goulaheau\RestBundle\Controller;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Goulaheau\RestBundle\Normalizer\EntityNormalizer;
use Goulaheau\RestBundle\Repository\RestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class RestController
 *
 * @package Goulaheau\RestBundle\Controller
 *
 * @Rout
 */
abstract class RestController extends AbstractController
{
    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var RestRepository
     */
    protected $repository;

    /**
     * @var EntityManagerInterface
     */
    protected $manager;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * RestController constructor.
     *
     * @param string                 $entityClass
     * @param RestRepository         $repository
     * @param EntityManagerInterface $manager
     * @param SerializerInterface    $serializer
     * @param ValidatorInterface     $validator
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function __construct(
        string $entityClass,
        RestRepository $repository,
        EntityManagerInterface $manager,
        ValidatorInterface $validator
    ) {
        $this->entityClass = $entityClass;
        $this->repository = $repository;
        $this->manager = $manager;
        $this->validator = $validator;

        $this->serializer = $this->createSerializer();
    }

    /**
     * @Route("", methods={"GET"})
     */
    public function search(): ?JsonResponse
    {
        $entities = $this->repository->findAll();

        return $this->normalizeAndJson($entities);
    }

    /**
     * @Route("/{id}", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function find(string $id): ?JsonResponse
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException();
        }

        return $this->normalizeAndJson($entity);
    }

    /**
     * @Route("", methods={"POST"})
     */
    public function create(Request $request): ?JsonResponse
    {
        $entity = $this->deserialize($request->getContent());

        $this->manager->persist($entity);
        $this->manager->flush();

        return $this->normalizeAndJson($entity);
    }

    /**
     * @Route("/{id}", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function update(string $id, Request $request): ?JsonResponse
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException();
        }

        $this->deserialize($request->getContent(), $entity);

        $this->manager->flush();

        return $this->normalizeAndJson($entity);
    }

    /**
     * @Route("/{id}", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function delete(string $id): ?JsonResponse
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException();
        }

        $this->manager->remove($entity);
        $this->manager->flush();

        return $this->normalizeAndJson([]);
    }

    /**
     * TODO: Utiliser JMS/Serializer
     * @return Serializer
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    protected function createSerializer(): Serializer
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

        $normalizers = [new EntityNormalizer($this->manager, $classMetadataFactory), new GetSetMethodNormalizer($classMetadataFactory)];
        $encoders = [new JsonEncoder()];

        return new Serializer($normalizers, $encoders);
    }

    /**
     * @param $data
     * @return JsonResponse
     */
    protected function normalizeAndJson($data)
    {
        return $this->json($this->normalize($data));
    }

    /**
     * @param $data
     * @return array|bool|float|int|mixed|string
     */
    protected function normalize($data)
    {
        return $this->serializer->normalize($data, null, ['groups' => 'read']);
    }

    /**
     * @param      $data
     * @param null $toEntity
     * @return object
     */
    protected function deserialize($data, $toEntity = null)
    {
        $context = ['groups' => 'update'];

        if ($toEntity) {
            $context['object_to_populate'] = $toEntity;
        }

        return $this->serializer->deserialize($data, $this->entityClass, 'json', $context);
    }
}
