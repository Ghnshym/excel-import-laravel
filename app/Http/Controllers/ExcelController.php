<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExcelController extends Controller
{
    public function index()
    {
        return view('upload');
    }

    public function import(Request $request)
{
    // Validate the uploaded file
    $request->validate([
        'file' => 'required|mimes:xlsx',
    ]);

    // Generate a unique table
    $tableName = 'dynamic_table_' . now()->format('YmdHis');

    // Store the uploaded file
    $file = $request->file('file');
    $filePath = Storage::putFile('uploads', $file);

    $data = Excel::toArray([], $filePath);

    $dataRows = array_slice($data[0], 1);

    $headers = $data[0][0];
    $validatedHeaders = [];
    foreach ($headers as $header) {
        $sanitizedHeader = preg_replace('/[^a-zA-Z0-9_]/', '_', trim($header));
        $finalHeader = !empty($sanitizedHeader) ? $sanitizedHeader : 'default_column_' . Str::random(8);
        $validatedHeaders[] = $finalHeader;
    }

    // Use Laravel's Schema Builder to dynamically create a new table
    Schema::create($tableName, function ($table) use ($validatedHeaders) {
        $table->id();
        foreach ($validatedHeaders as $header) {
            $table->string($header)->nullable();
        }
        $table->timestamps();
    });

    foreach ($dataRows as $row) {
        DB::table($tableName)->insert(array_combine($validatedHeaders, $row));
    }

    return view('display', [
        'data' => $dataRows, 
        'headers' => $headers, 
        'tableName' => $tableName,
    ]);
}

}
