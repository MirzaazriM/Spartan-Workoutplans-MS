<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/28/18
 * Time: 9:26 AM
 */

namespace Application\Controller;


use Model\Entity\Names;
use Model\Entity\NamesCollection;
use Model\Entity\Plan;
use Model\Entity\PlanCollection;
use Model\Entity\ResponseBootstrap;
use Model\Service\WorkoutPlanService;
use Symfony\Component\HttpFoundation\Request;

class WorkoutPlansController
{

    private $workoutPlansService;

    public function __construct(WorkoutPlanService $workoutPlansService)
    {
        $this->workoutPlansService = $workoutPlansService;
    }


    /**
     * Get plan by id
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(Request $request):ResponseBootstrap {
        // get data
        $id = $request->get('id');
        $lang = $request->get('lang');
        $state = $request->get('state');

        // create response object
        $response = new ResponseBootstrap();

        // check if parameters are present
        if(isset($id) && isset($lang) && isset($state)){
            return $this->workoutPlansService->getPlan($id, $lang, $state);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get workout plans list
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function getList(Request $request):ResponseBootstrap {
        // get data
        $from = $request->get('from');
        $limit = $request->get('limit');
        $state = $request->get('state');
        $lang = $request->get('lang');

        // create response object
        $response = new ResponseBootstrap();

        // check if parameters are present
        if(isset($from) && isset($limit)){ // && isset($state)
            return $this->workoutPlansService->getListOfWorkoutPlans($from, $limit, $state, $lang);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get plans by parametars
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPlans(Request $request):ResponseBootstrap {
        // get data
        $lang = $request->get('lang');
        $app = $request->get('app');
        $like = $request->get('like');
        $state = $request->get('state');

        // create response object
        $response = new ResponseBootstrap();

        // check if data is present
        if(!empty($lang) && !empty($state)){
            return $this->workoutPlansService->getPlans($lang, $state, $app, $like);
        }else{
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get plans by ids
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getIds(Request $request):ResponseBootstrap {
        // get data
        $ids = $request->get('ids');
        $lang = $request->get('lang');
        $state = $request->get('state');

        // convert ids string to an array
        $ids = explode(',', $ids);

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(!empty($ids) && !empty($lang) && !empty($state)){
            return $this->workoutPlansService->getPlansById($ids, $lang, $state);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Delete plan
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function delete(Request $request):ResponseBootstrap {
        // get data
        $id = $request->get('id');

        // create response object
        $response = new ResponseBootstrap();

        // check if data is present
        if(isset($id)){
            return $this->workoutPlansService->deletePlan($id);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Release plan
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function postRelease(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($id)){
            return $this->workoutPlansService->releasePlan($id);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Add plan
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function post(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $thumbnail = $data['thumbnail'];
        $rawName = $data['raw_name'];
        $type = $data['type'];
        $names = $data['names'];
        $tags = $data['tags'];
        $workouts = $data['workouts'];

        // create names collection
        $namesCollection = new NamesCollection();
        // set names into names collection
        foreach($names as $name){
            $temp = new Names();
            $temp->setName($name['name']);
            $temp->setDescription($name['description']);
            $temp->setLang($name['language']);

            $namesCollection->addEntity($temp);
        }

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($namesCollection) && isset($workouts) && isset($tags) && isset($thumbnail) && isset($rawName) && isset($type)){
            return $this->workoutPlansService->createPlan($namesCollection, $workouts, $tags, $thumbnail, $rawName, $type);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Edit plan
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function put(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        $thumbnail = $data['thumbnail'];
        $rawName = $data['raw_name'];
        $type = $data['type'];
        $names = $data['names'];
        $tags = $data['tags'];
        $workouts = $data['workouts'];

        // create names collection
        $namesCollection = new NamesCollection();

        // set names into names collection
        foreach($names as $name){
            $temp = new Names();
            $temp->setName($name['name']);
            $temp->setDescription($name['description']);
            $temp->setLang($name['language']);

            $namesCollection->addEntity($temp);
        }

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($id) && isset($namesCollection) && isset($workouts) && isset($tags) && isset($thumbnail) && isset($rawName) && isset($type)){
            return $this->workoutPlansService->editPlan($id, $namesCollection, $workouts, $tags, $thumbnail, $rawName, $type);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get total number of workout plans
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function getTotal(Request $request):ResponseBootstrap {
        // call service for response
        return $this->workoutPlansService->getTotal();
    }

}