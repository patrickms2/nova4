<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\UsuariosAddRequest;
use App\Http\Requests\UsuariosEditRequest;
use App\Models\Taxi\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Exception;
class UsuariosController extends Controller
{


	/**
     * List table records
	 * @param  \Illuminate\Http\Request
     * @param string $fieldname //filter records by a table field
     * @param string $fieldvalue //filter value
     * @return \Illuminate\View\View
     */
	function index(Request $request, $fieldname = null , $fieldvalue = null){
		$query = Usuario::query();
		if($request->search){
			$search = trim($request->search);
			Usuario::search($query, $search);
		}
		$query->join("tipos_usuarios", "usuarios.tipo_id", "=", "tipos_usuarios.id");
		$orderby = $request->orderby ?? "usuarios.id";
		$ordertype = $request->ordertype ?? "desc";
		$query->orderBy($orderby, $ordertype);
		if($fieldname){
			$query->where($fieldname , $fieldvalue); //filter by a single field name
		}
		// if request format is for export example:- product/index?export=pdf
		if($this->getExportFormat()){
			return $this->ExportList($query); // export current query
		}
		$records = $this->paginate($query, Usuario::listFields());
        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
	}
	/**
     * Load csv|json data
     * @return data
     */
	function importdata(Request $request){
		$importSettings = config("upload.import");
		$maxFileSize = intval($importSettings["max_file_size"]) * 1000; //in kilobyte
		$validator = Validator::make($request->all(),
			[
				"file" => "file|required|max:$maxFileSize|mimes:csv,txt",
			]
		);
		if ($validator->fails()) {
			return $this->reject($validator->errors(), 400);
		}
		$csvOptions = array(
			'fields' => '', //leave empty to use the first row as the columns
			'delimiter' => ',',
			'quote' => '"'
		);
		$filePath = $request->file('file')->getRealPath();
		$modeldata = $this->parse_csv_data($filePath, $csvOptions);
		$result = Usuario::insert($modeldata);
		return $this->respond($result);
	}


	/**
     * Select table record by ID
	 * @param string $rec_id
     * @return \Illuminate\View\View
     */
	function view($rec_id = null){
		$query = Usuario::query();
		$record = $query->findOrFail($rec_id, Usuario::viewFields());
		return $this->respond($record);
	}


	/**
     * Save form record to the table
     * @return \Illuminate\Http\Response
     */
	function add(UsuariosAddRequest $request){
		$modeldata = $request->validated();
		$modeldata['password'] = bcrypt($modeldata['password']);

		//save Usuarios record
		$record = Usuario::create($modeldata);
		$rec_id = $record->id;
		return $this->respond($record);
	}


	/**
     * Update table record with form data
	 * @param string $rec_id //select record by table primary key
     * @return \Illuminate\View\View;
     */
	function edit(UsuariosEditRequest $request, $rec_id = null){
		$query = Usuario::query();
		$record = $query->findOrFail($rec_id, Usuarios::editFields());
		if ($request->isMethod('post')) {
			$modeldata = $request->validated();
			$modeldata['password'] = bcrypt($modeldata['password']);
			$record->update($modeldata);
		}
		return $this->respond($record);
	}


	/**
     * Delete record from the database
	 * Support multi delete by separating record id by comma.
	 * @param  \Illuminate\Http\Request
	 * @param string $rec_id //can be separated by comma
     * @return \Illuminate\Http\Response
     */
	function delete(Request $request, $rec_id = null){
		$arr_id = explode(",", $rec_id);
		$query = Usuario::query();
		$query->whereIn("id", $arr_id);
		$query->delete();
		return $this->respond($arr_id);
	}


	/**
     * Export table records to different format
	 * supported format:- PDF, CSV, EXCEL, HTML
	 * @param \Illuminate\Database\Eloquent\Model $query
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
	private function ExportList($query){
		ob_end_clean(); // clean any output to allow file download
		$filename = "ListUsuariosReport-" . date_now();
		$format = $this->getExportFormat();
		if($format == "print"){
			$records = $query->get(Usuarios::exportListFields());
			return view("reports.usuarios-list", ["records" => $records]);
		}
		elseif($format == "pdf"){
			$records = $query->get(Usuarios::exportListFields());
			$pdf = PDF::loadView("reports.usuarios-list", ["records" => $records]);
			return $pdf->download("$filename.pdf");
		}
		elseif($format == "csv"){
			return Excel::download(new UsuariosListExport($query), "$filename.csv", \Maatwebsite\Excel\Excel::CSV);
		}
		elseif($format == "excel"){
			return Excel::download(new UsuariosListExport($query), "$filename.xlsx", \Maatwebsite\Excel\Excel::XLSX);
		}
	}
}
