<?php

namespace Goulaheau\RestBundle\Controller;

use App\Entity\User;
use Goulaheau\RestBundle\Entity\RestEntity;
use Goulaheau\RestBundle\Repository\RestRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RestController
 *
 * @package Goulaheau\RestBundle\Controller
 */
abstract class RestController extends AbstractController
{
    /**
     * @var RestRepository
     */
    protected $repository;

    public function __construct(RestRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @Route("/")
     */
    public function search(): ?JsonResponse
    {
        $entities = $this->repository->findAll();

        return $this->json($entities);
    }

    /**
     * @Route("/{id}")
     */
    public function find(string $id): ?JsonResponse
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException();
        }

        return $this->json($entity);
    }
}
