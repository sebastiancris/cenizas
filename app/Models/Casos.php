<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Auth;
use DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\CasosMail;

class Casos extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idUser',
        'tipo',
        'localidad',
        'region_id',
        'comuna_id',
        'direccion',
        'asunto',
        'descripcion',
        'archivo',
        'estado',
        'respuesta',
        'archivo_respuesta',
        'created_at',
        'updated_at'
    ];

     public static function validar(array $data)
    {
         $validator = Validator::make( $data,[
            'tipo' => 'required|string|max:255',
            'localidad' => 'required',
            'region' => 'required',
            'comuna' => 'required',
            'direccion' => 'required|string|max:255',
            'asunto' => 'required|string|max:255',  
            'descripcion' => 'required|string|max:2500', 
            // 'archivo' => 'required|file|mimes:pdf,zip,rar|max:20480',
            ]);

        return $validator;
    }

     public static function crearCaso($request)
    {
        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo');
            $nombreArchivo = 'caso_' . auth()->id() . "_" . date('Ymd_His') . "." . $archivo->getClientOriginalExtension();
            $archivo->storeAs('public/archivos', $nombreArchivo);
        } else {
            $nombreArchivo = null;
        }

        $insertedId = \DB::table('casos')->insertGetId([
            'idUser' => auth()->id(),
            'tipo' => $request->tipo,
            'localidad' => $request->localidad,
            'region_id' => $request->region,
            'comuna_id' => $request->comuna,
            'direccion' => $request->direccion,
            'asunto' => $request->asunto,
            'descripcion' => $request->descripcion,
            'archivo' => $nombreArchivo,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        
        return $insertedId;
    }


    public static function respuestaCaso($id)
    {
        $caso = Casos::join('users', 'casos.idUser', '=', 'users.id')
                 ->join('regiones', 'casos.region_id', '=', 'regiones.id')
                 ->join('comunas', 'casos.comuna_id', '=', 'comunas.id')
                 ->select('casos.*', 'users.*', 'regiones.nombre as region', 'comunas.nombre as comuna','casos.id as casoid')
                 ->findOrFail($id);
  
        return $caso;
    }  

    public static function getCasosUsu($id)
    {
         $casos = DB::table('casos')
        ->where('casos.idUser', $id)
        ->select(
            'casos.id AS id_caso', 
            'casos.tipo', 
            DB::raw("CASE 
                        WHEN estado = 1 THEN 'Pendiente'
                        WHEN estado = 2 THEN 'Resuelto'
                     END AS estado"),
            'created_at'
        )
        ->get();

        foreach ($casos as $caso) {
            $caso->created_at = Carbon::parse($caso->created_at)->format('d/m/Y');
        }

        return $casos;
    }      

    public static function respuestaCasoAdmin($id)
    {
        $caso = Casos::join('users', 'casos.idUser', '=', 'users.id')
                 ->join('regiones', 'casos.region_id', '=', 'regiones.id')
                 ->join('comunas', 'casos.comuna_id', '=', 'comunas.id')
                 ->select('casos.*', 'users.*', 'regiones.nombre as region', 'comunas.nombre as comuna','casos.id as casoid')
                 ->findOrFail($id);

        return $caso;
    }

    public static function responderCaso($id)
    {
        $caso = Casos::join('users', 'casos.idUser', '=', 'users.id')
                 ->join('regiones', 'casos.region_id', '=', 'regiones.id')
                 ->join('comunas', 'casos.comuna_id', '=', 'comunas.id')
                 ->select('casos.*', 'users.*', 'regiones.nombre as region', 'comunas.nombre as comuna','casos.id as casoid')
                 ->findOrFail($id);
        
        return $caso;
    }

    public static function casosUsuarioAdmin()
    {
        $zona = auth()->user()->zona;
   
        $casos = Casos::join('users', 'casos.idUser', '=', 'users.id')
                        ->select('casos.*', 'users.name as nombre_usuario','casos.id as caso_id')
                        ->where('users.zona',$zona)
                        ->get();

        $casos->transform(function ($caso) {
            $caso->fecha_creacion = Carbon::parse($caso->created_at)->format('d-m-Y');
            $caso->estado = $caso->estado === null || $caso->estado == 0 ? 'ABIERTO' : 'CERRADO';
            $caso->respuesta = $caso->respuesta === null ? '<a href="' . route("responderCaso", ['id' => $caso->caso_id]) . '">RESPONDER</a>' : '<a href="' . route("respuestaCasoAdmin", ['id' => $caso->caso_id]) . '">VER RESPUESTA</a>';
            return $caso;
        });

        return $casos;
    }

    public static function cerrarCaso($request)
    {
        $archivo = $request->file('archivo');

        $nombreArchivo = 'res_caso_' . $request->casoId . "_" . date('Ymd_His') . "." . $archivo->getClientOriginalExtension();
        $archivo->storeAs('public/archivos', $nombreArchivo);

        $caso = Casos::findOrFail($request->casoId);
        $caso->respuesta = $request->input('respuesta');
        $caso->archivo_respuesta = $nombreArchivo;
        $caso->estado = 1;
        $caso->updated_at = Carbon::now();
        $caso->save();

        return $caso;
    }

    public static function casosUsuario()
    {
        $casos = Casos::where('idUser', '=', auth()->id())
            ->join('users', 'casos.idUser', '=', 'users.id')
            ->select('casos.*', 'users.name as nombre_usuario', 'casos.id as caso_id')
            ->get();

        $casos->transform(function ($caso) {
            $caso->fecha_creacion = Carbon::parse($caso->created_at)->format('d-m-Y');
            $caso->estado = $caso->estado === null || $caso->estado == 0 ? 'ABIERTO' : 'CERRADO';
            $caso->respuesta = $caso->respuesta === null ? 'EN ESPERA' : '<a href="' . route("respuestaCaso", ['id' => $caso->caso_id]) . '">VER RESPUESTA</a>';
            return $caso;
        });

        return $casos;
    }

    public static function casosEmail($caso_id)
    {
        try {
        $email = Casos::where('casos.id', '=', $caso_id)
            ->join('users', 'casos.idUser', '=', 'users.id')
            ->select('users.email as email')
            ->first();

        if (!$email) {
            throw new Exception('No se encontró el correo electrónico para el caso ID: ' . $caso_id);
        }

        Mail::to($email->email)->send(new CasosMail());

        return true;
        } catch (Exception $e) {
            Log::error('Error al enviar el correo: ' . $e->getMessage());
            return false;
        }
    }
}
