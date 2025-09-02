<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CertificateController extends Controller
{
    // GET /api/certificates?q=texto&per_page=10&page=1
public function index(Request $request)
{
    $q = Certificado::query()
        ->select([
            'id','codigo','Numero_Cis','puerto_salida','pais_destino','ruta_viaje',
            'procedencia','via','importador','exportador','direccion_importador',
            'direccion_exportador','dictamen','observaciones','fecha_precuarentena',
            'nombre_predio','municipio','vereda','created_at'
        ])
        ->when($request->search, fn($qq) =>
            $qq->where('codigo','like','%'.$request->search.'%')
            ->orWhere('Numero_Cis','like','%'.$request->search.'%')
        )
        // FILTROS POR FECHA - NUEVO
        ->when($request->start_date, fn($qq) => 
            $qq->whereDate('created_at', '>=', $request->start_date)
        )
        ->when($request->end_date, fn($qq) => 
            $qq->whereDate('created_at', '<=', $request->end_date)
        )
        ->orderByDesc('id');

    $perPage = (int)($request->per_page ?? 10);
    $page    = (int)($request->page ?? 1);

    $p = $q->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'data'     => $p->items(),
        'total'    => $p->total(),
        'page'     => $p->currentPage(),
        'per_page' => $p->perPage(),
    ]);
}

public function show($id)
{
    $cert = Certificado::with('productos')->findOrFail($id);
    return response()->json(['data' => $cert]);
}

public function showByCode($code)
{
        $certificate = Certificado::with('productos')
            ->where('codigo', $code)
            ->orWhere('Numero_Cis', $code)
            ->first();
        
        if (!$certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Certificado no encontrado'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $certificate
        ]);
}
    // POST /api/certificates
public function store(Request $request)
{
    $data = $request->validate([
        'codigo'               => ['required','string','max:50', Rule::unique('certificados','codigo')],
        'Numero_Cis'           => ['nullable','string','max:50'],
        'puerto_salida'        => ['nullable','string','max:255'],
        'pais_destino'         => ['nullable','string','max:255'],
        'ruta_viaje'           => ['nullable','string','max:255'],
        'procedencia'          => ['nullable','string','max:100'],
        'via'                  => ['nullable','string','max:100'],
        'importador'           => ['nullable','string','max:255'],
        'exportador'           => ['nullable','string','max:255'],
        'direccion_importador' => ['nullable','string','max:255'],
        'direccion_exportador' => ['nullable','string','max:255'],
        'dictamen'             => ['nullable','string','max:255'],
        'observaciones'        => ['nullable','string'],
        'fecha_precuarentena'  => ['nullable','date'],
        'nombre_predio'        => ['nullable','string','max:255'],
        'municipio'            => ['nullable','string','max:255'],
        'vereda'               => ['nullable','string','max:255'],

        // si además creas caninos en la misma llamada:
        'productos'                   => ['array'],
        'productos.*.cantidad'        => ['nullable','integer','min:1'],
        'productos.*.unidad'          => ['nullable','string','max:100'],
        'productos.*.producto'        => ['nullable','string','max:190'],
        'productos.*.presentacion'    => ['nullable','string','max:190'],
        'productos.*.code_chip'       => ['nullable','string','max:100'],
        'productos.*.raza'            => ['nullable','string','max:190'],
        'productos.*.empaque'         => ['nullable','string','max:100'],
        'productos.*.sexo'            => ['nullable','string','max:20'],
        'productos.*.edad'            => ['nullable','string','max:50'],
        'productos.*.valor_fob'       => ['nullable','numeric'],
    ]);

    return DB::transaction(function () use ($data) {
        $cert = Certificado::create($data);

        if (!empty($data['productos'])) {
            $items = collect($data['productos'])->map(function ($p) {
                return [
                    'cantidad'     => (int)($p['cantidad'] ?? 1),
                    'unidad'       => $p['unidad'] ?? 'UNIDADES/UNITS',
                    'producto'     => $p['producto'] ?? 'PERROS COMPAÑÍA',
                    'presentacion' => $p['presentacion'] ?? null,
                    'code_chip'    => $p['code_chip'] ?? null,
                    'raza'         => $p['raza'] ?? null,
                    'empaque'      => $p['empaque'] ?? 'No Aplica',
                    'sexo'         => $p['sexo'] ?? null,
                    'edad'         => $p['edad'] ?? null,
                    'valor_fob'    => $p['valor_fob'] ?? 0,
                ];
            })->all();
            $cert->productos()->createMany($items);
        }

        return response()->json(['message' => 'created', 'data' => $cert->load('productos')], 201);
    });
}

    // PUT/PATCH /api/certificates/{certificado}
    public function update(Request $request, $id)
{
    $cert = Certificado::findOrFail($id);

    $data = $request->validate([
        'codigo'               => ['required','string','max:50', Rule::unique('certificados','codigo')->ignore($cert->id)],
        'Numero_Cis'           => ['nullable','string','max:50'],
        'puerto_salida'        => ['nullable','string','max:255'],
        'pais_destino'         => ['nullable','string','max:255'],
        'ruta_viaje'           => ['nullable','string','max:255'],
        'procedencia'          => ['nullable','string','max:100'],
        'via'                  => ['nullable','string','max:100'],
        'importador'           => ['nullable','string','max:255'],
        'exportador'           => ['nullable','string','max:255'],
        'direccion_importador' => ['nullable','string','max:255'],
        'direccion_exportador' => ['nullable','string','max:255'],
        'dictamen'             => ['nullable','string','max:255'],
        'observaciones'        => ['nullable','string'],
        'fecha_precuarentena'  => ['nullable','date'],
        'nombre_predio'        => ['nullable','string','max:255'],
        'municipio'            => ['nullable','string','max:255'],
        'vereda'               => ['nullable','string','max:255'],

        'productos'                 => ['array'],
        'productos.*.cantidad'      => ['nullable','integer','min:1'],
        'productos.*.unidad'        => ['nullable','string','max:100'],
        'productos.*.producto'      => ['nullable','string','max:190'],
        'productos.*.presentacion'  => ['nullable','string','max:190'],
        'productos.*.code_chip'     => ['nullable','string','max:100'],
        'productos.*.raza'          => ['nullable','string','max:190'],
        'productos.*.empaque'       => ['nullable','string','max:100'],
        'productos.*.sexo'          => ['nullable','string','max:20'],
        'productos.*.edad'          => ['nullable','string','max:50'],
        'productos.*.valor_fob'     => ['nullable','numeric'],
    ]);

    return DB::transaction(function () use ($cert, $data) {
        $cert->update($data);

        // Si quieres reemplazar completamente la lista de caninos:
        if (array_key_exists('productos', $data)) {
            $cert->productos()->delete();
            if (!empty($data['productos'])) {
                $cert->productos()->createMany($data['productos']);
            }
        }

        return response()->json(['message' => 'updated', 'data' => $cert->load('productos')]);
    });
}

   public function destroy(int $id)   // ← o public function destroy($id)
{
    $certificado = Certificado::findOrFail($id);   // devuelve un *Model*, no una Collection

    if ($certificado->productos()->exists()) {
        return response()->json([
            'message' => 'No se puede eliminar: el certificado tiene caninos asociados.',
        ], 409); // Conflict
    }

    try {
        $certificado->delete();
        return response()->json(['deleted' => true], 200);
    } catch (QueryException $e) {
        if ($e->getCode() === '23000') { // FK constraint, etc.
            return response()->json([
                'message' => 'No se puede eliminar por restricciones de integridad.',
            ], 409);
        }
        throw $e;
    }
}
}
