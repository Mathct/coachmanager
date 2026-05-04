<?php

namespace App\Controller;

use App\Repository\PlayerRepository;
use App\Repository\RencontreRepository;
use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        TeamRepository $teamRepository,
        PlayerRepository $playerRepository,
        RencontreRepository $rencontreRepository
    ): Response {
        return $this->render('home/index.html.twig', [
            'team_count' => $teamRepository->count([]),
            'player_count' => $playerRepository->count([]),
            'rencontre_count' => $rencontreRepository->count([]),
            'latest_rencontres' => $rencontreRepository->findBy([], ['date' => 'DESC'], 5),
        ]);
    }
}
