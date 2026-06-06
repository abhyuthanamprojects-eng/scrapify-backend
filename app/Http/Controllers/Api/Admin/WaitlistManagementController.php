<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Waitlist;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class WaitlistManagementController extends Controller
{
    use ApiResponseTrait;

    #[OA\Get(
        path: "/api/admin/waitlist",
        operationId: "adminListWaitlist",
        tags: ["Admin"],
        summary: "List all waitlist entries with filters",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $query = Waitlist::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $waitlist = $query->latest()->paginate($request->per_page ?? 20);

        return $this->paginatedResponse('admin.waitlist_fetched', $waitlist);
    }

    #[OA\Post(
        path: "/api/admin/waitlist/{id}/status",
        operationId: "adminUpdateWaitlistStatus",
        tags: ["Admin"],
        summary: "Update waitlist entry status",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", enum: ["new", "contacted", "planned", "launched", "closed"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Status updated")
        ]
    )]
    public function updateStatus(Request $request, $id)
    {
        $waitlist = Waitlist::find($id);

        if (!$waitlist) {
            return $this->errorResponse('general.not_found', 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,contacted,planned,launched,closed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $waitlist->update(['status' => $request->status]);

        return $this->successResponse('admin.waitlist_updated', $waitlist);
    }
    #[OA\Get(
        path: "/api/admin/waitlist/export",
        operationId: "adminExportWaitlist",
        tags: ["Admin"],
        summary: "Export waitlist entries as CSV",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "CSV file")
        ]
    )]
    public function export(Request $request)
    {
        $query = Waitlist::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        $waitlist = $query->latest()->get();

        $filename = "waitlist_export_" . date('Y-m-d_H-i-s') . ".csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['ID', 'Name', 'Phone', 'Email', 'City', 'State', 'Location Name', 'Latitude', 'Longitude', 'Status', 'Date'];

        $callback = function () use ($waitlist, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($waitlist as $entry) {
                fputcsv($file, [
                    $entry->id,
                    $entry->name,
                    $entry->phone,
                    $entry->email,
                    $entry->city,
                    $entry->state,
                    $entry->location_name,
                    $entry->latitude,
                    $entry->longitude,
                    $entry->status,
                    $entry->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
