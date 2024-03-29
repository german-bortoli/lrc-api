<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Http\Requests\ServiceUpdateRequest;
use App\Http\Resources\ServiceCollection;
use App\Http\Resources\ServiceResource;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{

    /**
     * Set authentication middleware
     * ServiceController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except(['index' , 'show']);
    }

    /**
     * Get a list of services sorted by distance from the center
     *
     * @param Request $request
     * @return ServiceCollection
     */
    public function index(Request $request)
    {
        // Request values
        $center_input = ['lat' => $request->get('lat', 0), 'lng' => $request->get('lng', 0)];
        $sort = $request->get('sort', 'asc');
        $distance = intval($request->input('distance'));
        $paginated = (bool) $request->input('paginated');

        // Service Query
        $sq = Service::query();

        $center = new Point($center_input['lat'], $center_input['lng']);

        // Filter by kilometers
        if ($distance > 0) {
            $sq->kmDistance($center, $distance);
        }

        // Sort by distance from client center
        $sq->orderByDistanceSphere('geolocation', $center, $sort);

        $services = $paginated ? $sq->paginate() : $sq->get();

        // Return the resource
        return new ServiceCollection($services);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ServiceRequest $request
     * @return ServiceResource
     */
    public function store(ServiceRequest $request)
    {
        $data = $request->all();

        // Convert json to point
        $data['geolocation'] = new Point($data['geolocation']['lat'], $data['geolocation']['lng']);

        $service = Service::create($data);

        return new ServiceResource($service);
    }

    /**
     * Display the specified resource.
     *
     * @param Service $service
     * @return ServiceResource
     */
    public function show(Service $service)
    {
        return new ServiceResource($service);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ServiceUpdateRequest $request
     * @param Service $service
     * @return ServiceResource
     */
    public function update(ServiceUpdateRequest $request, Service $service)
    {
        $data = $request->all();

        // Convert json to point
        if (isset($data['geolocation'])) {
            $data['geolocation'] = new Point($data['geolocation']['lat'], $data['geolocation']['lng']);
        }

        $service->fill($data);
        $service->save();

        return new ServiceResource($service);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Service $service
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(Service $service)
    {
        $deleted = $service->delete();

        if ($deleted) {
            return response()->json([
                'message' => 'The service has been deleted',
            ]);
        }

        return response(422)->json(['message' => 'There were an error while deleting the service']);
    }
}
