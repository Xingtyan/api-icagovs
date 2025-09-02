<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificado extends Model
{
    protected $table = 'certificados';
    protected $primaryKey = 'id';

    // La BD pone created_at por defecto; Eloquent no toca timestamps
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'Numero_Cis',
        'puerto_salida',
        'pais_destino',
        'ruta_viaje',
        'procedencia',
        'via',
        'importador',
        'exportador',
        'direccion_importador',
        'direccion_exportador',
        'dictamen',
        'observaciones',
        'fecha_precuarentena',
        'nombre_predio',
        'municipio',
        'vereda',
    ];

    protected $casts = [
        'fecha_precuarentena' => 'date:Y-m-d',
        'created_at'          => 'datetime',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'certificado_id');
    }
}
