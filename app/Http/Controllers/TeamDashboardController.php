<?php 

namespace App\Http\Controllers;

use App\Http\Requests\TeamDashboardRequest;
use App\Services\TeamDashboardService;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamDashboardController extends Controller
{
    public function __construct(private readonly TeamDashboardService $service) {}

    public function index(TeamDashboardRequest $request): JsonResource
    {
        return new JsonResource($this->service->build(
            $request->user(),
            $request->targetDate(),
            $request->safe()->only(['status', 'sort']),
        ));
    }
}