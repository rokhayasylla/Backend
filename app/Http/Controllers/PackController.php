<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackFormRequest;
use App\Services\PackService;
use Illuminate\Http\Request;

class PackController extends Controller
{
    protected $packService;

    public function __construct(PackService $packService)
    {
        $this->packService = $packService;
    }

    public function index()
    {
        $packs = $this->packService->index();
        return response()->json($packs, 200);
    }

    public function store(PackFormRequest $request)
    {
        try {
            $data = $request->validated();
            $imageFile = $request->hasFile('image_path') ? $request->file('image_path') : null;

            $pack = $this->packService->store($data, $imageFile);
            return response()->json($pack, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la création du pack: ' . $e->getMessage()], 400);
        }
    }

    public function show(string $id)
    {
        $pack = $this->packService->show($id);
        return response()->json($pack, 200);
    }

    public function update(PackFormRequest $request, string $id)
    {
        try {
            // Préparer les données incluant le fichier image si présent
            $data = $request->validated();
            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image');
            }

            $pack = $this->packService->update($data, $id);
            return response()->json($pack, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()], 400);
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->packService->destroy($id);
            return response('', 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()], 400);
        }
    }

    public function active()
    {
        $packs = $this->packService->getActivePacks();
        return response()->json($packs, 200);
    }
}
