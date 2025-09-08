<?php

namespace App\Imports;

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\District;
use App\Models\Region;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Illuminate\Support\Str;

class FacilitiesImport implements 
    ToModel, 
    WithHeadingRow, 
    WithChunkReading, 
    WithBatchInserts, 
    WithValidation, 
    SkipsEmptyRows, 
    WithEvents, 
    ShouldQueue
{
    use Queueable, InteractsWithQueue;

    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $facilityTypeCache = [];
    protected $districtCache = [];
    protected $regionCache = [];
    protected $importId;

    public function __construct(string $importId)
    {
        $this->importId = $importId;
    }

    public function model(array $row)
    {
        try {
            // Check if required fields are present
            if (empty($row['facility_name'])) {
                $this->skippedCount++;
                return null;
            }

            $facilityName = trim($row['facility_name']);

            if (strlen($facilityName) > 255) {
                $this->errors[] = "Facility name too long: " . substr($facilityName, 0, 50) . "...";
                $this->skippedCount++;
                return null;
            }

            // Check if facility already exists
            $existingFacility = Facility::where('name', $facilityName)->first();
            if ($existingFacility) {
                // Log that we're updating an existing facility
                $this->errors[] = "Info: Updating existing facility: " . $facilityName;
            }

            // Facility Type
            $facilityType = null;
            if (!empty($row['facility_type'])) {
                $typeName = trim($row['facility_type']);
                if (strlen($typeName) > 255) {
                    $this->errors[] = "Facility type name too long: " . substr($typeName, 0, 50) . "...";
                    $this->skippedCount++;
                    return null;
                }

                if (!isset($this->facilityTypeCache[$typeName])) {
                    $facilityTypeModel = FacilityType::firstOrCreate(
                        ['name' => $typeName],
                        ['is_active' => true]
                    );
                    $this->facilityTypeCache[$typeName] = $facilityTypeModel->name;
                }
                $facilityType = $this->facilityTypeCache[$typeName];
            }

            // Region (required - derive from district if not provided)
            $region = null;
            if (!empty($row['region'])) {
                $regionName = trim($row['region']);
                if (strlen($regionName) > 255) {
                    $this->errors[] = "Region name too long: " . substr($regionName, 0, 50) . "...";
                    $this->skippedCount++;
                    return null;
                }

                if (!isset($this->regionCache[$regionName])) {
                    $regionModel = Region::firstOrCreate(
                        ['name' => $regionName]
                    );
                    $this->regionCache[$regionName] = $regionModel->name;
                }
                $region = $this->regionCache[$regionName];
            } else {
                // If region is not provided, derive it from district or use a default
                // Note: region is required in validation, but keep fallback for safety
                $region = null;
            }

            // District (create if missing and link to region by name)
            $district = null;
            if (!empty($row['district'])) {
                $districtName = trim($row['district']);
                if (strlen($districtName) > 255) {
                    $this->errors[] = "District name too long: " . substr($districtName, 0, 50) . "...";
                    $this->skippedCount++;
                    return null;
                }

                // If region was not provided, try derive from district; else ensure we have a region
                if (empty($region)) {
                    $derivedRegion = Region::where('name', 'like', '%' . $districtName . '%')->first();
                    if ($derivedRegion) {
                        $region = $derivedRegion->name;
                    } else {
                        $region = 'Region ' . $districtName;
                        $regionModel = Region::firstOrCreate(['name' => $region]);
                        $region = $regionModel->name;
                    }
                }

                if (!isset($this->districtCache[$districtName])) {
                    $districtModel = District::firstOrCreate(
                        ['name' => $districtName],
                        ['region' => $region]
                    );
                    $this->districtCache[$districtName] = $districtModel->name;
                }
                $district = $this->districtCache[$districtName];
            }

            // Ensure region has a value (should never be null at this point)
            if (empty($region)) {
                $region = 'Default Region';
                $regionModel = Region::firstOrCreate(['name' => $region]);
                $region = $regionModel->name;
            }

            // Email validation
            $email = null;
            if (!empty($row['email'])) {
                $email = trim($row['email']);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[] = "Invalid email format for facility: " . $facilityName;
                    $this->skippedCount++;
                    return null;
                }
            }

            // Phone validation
            $phone = null;
            if (!empty($row['phone'])) {
                $phone = trim($row['phone']);
                if (strlen($phone) > 20) {
                    $this->errors[] = "Phone number too long for facility: " . $facilityName;
                    $this->skippedCount++;
                    return null;
                }
            }

            // Handle required fields that are system-managed
            $handledBy = null;
            
            // Always use the first available user or create a default
            $defaultUser = \App\Models\User::first();
            if ($defaultUser) {
                $handledBy = $defaultUser->id;
            } else {
                // Create a default user if none exists
                $defaultUser = \App\Models\User::create([
                    'name' => 'System Admin',
                    'username' => 'system_admin',
                    'email' => 'admin@warehouse.com',
                    'password' => bcrypt('password'),
                    'title' => 'System Administrator',
                    'is_active' => true,
                ]);
                $handledBy = $defaultUser->id;
            }

            // Final validation - ensure all required fields are present (after handledBy is determined)
            if (empty($facilityType) || empty($district) || empty($region) || empty($handledBy)) {
                $this->errors[] = "Missing required fields for facility: " . $facilityName;
                $this->skippedCount++;
                return null;
            }

            $this->importedCount++;

            // Update progress in cache
            Cache::increment($this->importId);

            // Create or update facility
            try {
                $facility = Facility::updateOrCreate([
                    'name' => $facilityName,
                ], [
                    'name' => $facilityName,
                    'facility_type' => $facilityType,
                    'district' => $district,
                    'region' => $region,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => !empty($row['address']) ? trim($row['address']) : null,
                    'handled_by' => $handledBy,
                    'is_active' => true,
                    'has_cold_storage' => false, // Default value
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Handle duplicate entry errors
                if ($e->getCode() == 23000) {
                    // Check if it's a duplicate name error
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        // Try to find existing facility by name
                        $existingFacility = Facility::where('name', $facilityName)->first();
                        if ($existingFacility) {
                            // Update the existing facility
                            $existingFacility->update([
                                'facility_type' => $facilityType,
                                'district' => $district,
                                'region' => $region,
                                'email' => $email,
                                'phone' => $phone,
                                'address' => !empty($row['address']) ? trim($row['address']) : null,
                                'handled_by' => $handledBy,
                                'is_active' => true,
                                'has_cold_storage' => false,
                            ]);
                            $facility = $existingFacility;
                            $this->errors[] = "Info: Successfully updated existing facility: " . $facilityName;
                        } else {
                            // This shouldn't happen, but handle it gracefully
                            $this->errors[] = "Error updating facility: " . $facilityName . " - " . $e->getMessage();
                            $this->skippedCount++;
                            return null;
                        }
                    } else {
                        // Other database constraint error
                        $this->errors[] = "Database constraint error for facility: " . $facilityName . " - " . $e->getMessage();
                        $this->skippedCount++;
                        return null;
                    }
                } else {
                    // Re-throw non-duplicate errors
                    throw $e;
                }
            }

            // We already persisted the facility above; do not return a model instance here.
            // Returning a persisted model can cause the Excel package to attempt a second bulk insert with explicit IDs.
            return null;

        } catch (\Exception $e) {
            $facilityName = $row['facility_name'] ?? 'Unknown';
            $this->errors[] = "Error processing facility '{$facilityName}': " . $e->getMessage();
            $this->skippedCount++;
            throw $e;
        }
    }

    public function rules(): array
    {
        return [
            'facility_name' => 'required|string|max:255',
            'facility_type' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                Cache::forget($this->importId);
                Log::info('Facilities import completed', [
                    'importId' => $this->importId,
                    'imported' => $this->importedCount,
                    'skipped' => $this->skippedCount,
                    'errors' => count($this->errors)
                ]);
            },
        ];
    }

    public function getResults()
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors,
        ];
    }
}
