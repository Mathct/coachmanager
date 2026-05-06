<?php

namespace App\Controller;

use App\Entity\Composition;
use App\Entity\CompositionPlayer;
use App\Entity\Player;
use App\Entity\Rencontre;
use App\Form\RencontreType;
use App\Repository\RencontreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rencontre')]
final class RencontreController extends AbstractController
{
    #[Route(name: 'app_rencontre_index', methods: ['GET'])]
    public function index(RencontreRepository $rencontreRepository): Response
    {
        return $this->render('rencontre/index.html.twig', [
            'rencontres' => $rencontreRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_rencontre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rencontre = new Rencontre();
        $form = $this->createForm(RencontreType::class, $rencontre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($rencontre);
            $entityManager->flush();

            return $this->redirectToRoute('app_rencontre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('rencontre/new.html.twig', [
            'rencontre' => $rencontre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_rencontre_show', methods: ['GET'])]
    public function show(Rencontre $rencontre): Response
    {
        return $this->render('rencontre/show.html.twig', [
            'rencontre' => $rencontre,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_rencontre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Rencontre $rencontre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RencontreType::class, $rencontre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_rencontre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('rencontre/edit.html.twig', [
            'rencontre' => $rencontre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/composition', name: 'app_rencontre_composition', methods: ['GET', 'POST'])]
    public function composition(Request $request, Rencontre $rencontre, EntityManagerInterface $entityManager): Response
    {
        $composition = $rencontre->getComposition();
        if ($composition === null) {
            $composition = (new Composition())->setRencontre($rencontre);
            $rencontre->setComposition($composition);
            $entityManager->persist($composition);
        }

        $players = $rencontre->getTeam()->getPlayers()->toArray();
        usort(
            $players,
            static fn (Player $a, Player $b): int => strcmp($a->getNom().' '.$a->getPrenom(), $b->getNom().' '.$b->getPrenom())
        );

        $entriesByPlayerId = [];
        foreach ($composition->getCompositionPlayers() as $entry) {
            $player = $entry->getPlayer();
            if ($player !== null) {
                $entriesByPlayerId[$player->getId()] = $entry;
            }
        }

        foreach ($players as $player) {
            if (!isset($entriesByPlayerId[$player->getId()])) {
                $entry = (new CompositionPlayer())
                    ->setComposition($composition)
                    ->setPlayer($player)
                    ->setStatus('');
                $composition->addCompositionPlayer($entry);
                $entriesByPlayerId[$player->getId()] = $entry;
            }
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('composition'.$rencontre->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            $formation = (string) $request->request->get('formation', $composition->getFormation());
            if (!in_array($formation, Composition::FORMATIONS, true)) {
                $this->addFlash('danger', 'La formation selectionnee est invalide.');

                return $this->redirectToRoute('app_rencontre_composition', ['id' => $rencontre->getId()]);
            }

            $composition->setFormation($formation);
            $composition->setIsValidated(true);
            [$titularCount, $hasGoalkeeper, $duplicateNumber] = $this->applyCompositionEntries(
                $request->request->all('entries'),
                $players,
                $entriesByPlayerId
            );

            if ($duplicateNumber !== null) {
                $this->addFlash('danger', sprintf('Le numero %d est utilise plusieurs fois.', $duplicateNumber));

                return $this->redirectToRoute('app_rencontre_composition', ['id' => $rencontre->getId()]);
            }

            if ($titularCount !== 11) {
                $this->addFlash('danger', 'Il faut exactement 11 titulaires pour valider une composition de foot.');
            } elseif (!$hasGoalkeeper) {
                $this->addFlash('danger', 'Il faut au moins un gardien (GK) parmi les titulaires.');
            } else {
                $this->addFlash('success', 'Composition enregistree.');
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_rencontre_composition', ['id' => $rencontre->getId()]);
        }

        return $this->render('rencontre/composition.html.twig', [
            'rencontre' => $rencontre,
            'composition' => $composition,
            'compositionSaved' => $composition->isValidated(),
            'players' => $players,
            'entriesByPlayerId' => $entriesByPlayerId,
            'formations' => Composition::FORMATIONS,
            'statuses' => CompositionPlayer::STATUSES,
            'positions' => CompositionPlayer::POSITIONS,
        ]);
    }

    /**
     * @param array<string, mixed> $submittedEntries
     * @param array<int, Player> $players
     * @param array<int, CompositionPlayer> $entriesByPlayerId
     * @return array{0:int,1:bool,2:?int}
     */
    private function applyCompositionEntries(array $submittedEntries, array $players, array $entriesByPlayerId): array
    {
        $usedNumbers = [];
        $titularCount = 0;
        $hasGoalkeeper = false;
        $duplicateNumber = null;

        foreach ($players as $player) {
            $playerId = (string) $player->getId();
            $entry = $entriesByPlayerId[$player->getId()];
            $submitted = $submittedEntries[$playerId] ?? [];

            $status = (string) ($submitted['status'] ?? CompositionPlayer::STATUS_ABSENT);
            if (!in_array($status, CompositionPlayer::STATUSES, true)) {
                $status = CompositionPlayer::STATUS_ABSENT;
            }

            $position = strtoupper(trim((string) ($submitted['position'] ?? '')));
            if ($position === '' || !in_array($position, CompositionPlayer::POSITIONS, true)) {
                $position = null;
            }

            $numberRaw = trim((string) ($submitted['number'] ?? ''));
            $number = ctype_digit($numberRaw) ? (int) $numberRaw : null;
            if ($number !== null && ($number < 1 || $number > 99)) {
                $number = null;
            }

            $entry->setStatus($status);
            $entry->setPositionCode($position);
            $entry->setJerseyNumber($number);
            $isPlaced = ((string) ($submitted['is_placed'] ?? '0')) === '1';
            $coordXRaw = trim((string) ($submitted['coord_x'] ?? ''));
            $coordYRaw = trim((string) ($submitted['coord_y'] ?? ''));
            $coordX = ctype_digit($coordXRaw) ? (int) $coordXRaw : null;
            $coordY = ctype_digit($coordYRaw) ? (int) $coordYRaw : null;
            if ($coordX !== null && ($coordX < 0 || $coordX > 100)) {
                $coordX = null;
            }
            if ($coordY !== null && ($coordY < 0 || $coordY > 100)) {
                $coordY = null;
            }
            if (!$isPlaced) {
                $coordX = null;
                $coordY = null;
            }
            $entry->setIsPlaced($isPlaced);
            $entry->setCoordX($coordX);
            $entry->setCoordY($coordY);

            if ($status === CompositionPlayer::STATUS_TITULAIRE) {
                ++$titularCount;
                if ($position === 'GK') {
                    $hasGoalkeeper = true;
                }
            }

            if ($number !== null) {
                if (isset($usedNumbers[$number])) {
                    $duplicateNumber = $number;
                }
                $usedNumbers[$number] = true;
            }
        }

        return [$titularCount, $hasGoalkeeper, $duplicateNumber];
    }

    #[Route('/{id}', name: 'app_rencontre_delete', methods: ['POST'])]
    public function delete(Request $request, Rencontre $rencontre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rencontre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($rencontre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rencontre_index', [], Response::HTTP_SEE_OTHER);
    }
}
