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
            'file' => 'required|mimes:xlsx,xls',
        ]);
    
        // Generate a unique table
        $tableName = 'dynamic_table_' . now()->format('YmdHis');
    
        // Store the uploaded file
        $file = $request->file('file');
        $filePath = Storage::putFile('uploads', $file);
    
        $data = Excel::toArray([], $filePath);
    
        // Remove empty rows (those with all empty cells)
        $dataRows = array_filter(array_slice($data[0], 1), function ($row) {
            return !empty(array_filter($row, 'strlen'));
        });
    
        if (empty($dataRows)) {
            // Handle the case where there is no data to import
            return redirect()->back()->with('error', 'No data found in the uploaded Excel file.');
        }
    
        // Get the first row as headers
        $headers = $data[0][0];
    
        // Filter out empty headers
        $nonEmptyHeaders = array_filter($headers, 'strlen');
    
        if (empty($nonEmptyHeaders)) {
            // Handle the case where all headers are empty
            return redirect()->back()->with('error', 'No valid column headers found in the uploaded Excel file.');
        }
    
        $validatedHeaders = [];
        foreach ($nonEmptyHeaders as $header) {
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
            // Filter out empty cells in the row
            $nonEmptyRow = array_filter($row, 'strlen');
            DB::table($tableName)->insert(array_combine($validatedHeaders, $nonEmptyRow));
        }
    
        return view('display', [
            'data' => $dataRows, 
            'headers' => $nonEmptyHeaders, 
            'tableName' => $tableName,
        ]);
    }
    

}
