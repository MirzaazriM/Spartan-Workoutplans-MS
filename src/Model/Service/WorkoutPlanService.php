<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/28/18
 * Time: 9:26 AM
 */

namespace Model\Service;


use Component\LinksConfiguration;
use Model\Core\Helper\Monolog\MonologSender;
use Model\Entity\NamesCollection;
use Model\Entity\Plan;
use Model\Entity\PlanCollection;
use Model\Entity\ResponseBootstrap;
use Model\Mapper\WorkoutPlansMapper;
use Model\Service\Facade\GetPlansFacade;

class WorkoutPlanService extends LinksConfiguration
{

    private $workoutPlansMapper;
    private $configuration;
    private $monologHelper;

    public function __construct(WorkoutPlansMapper $workoutPlansMapper)
    {
        $this->workoutPlansMapper = $workoutPlansMapper;
        $this->configuration = $workoutPlansMapper->getConfiguration();
        $this->monologHelper = new MonologSender();
    }


    /**
     * Get plan service
     *
     * @param int $id
     * @param string $lang
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPlan(int $id, string $lang, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setId($id);
            $entity->setLang($lang);
            $entity->setState($state);

            // get response from database
            $res = $this->workoutPlansMapper->getPlan($entity);
            $id = $res->getId();

            // get workout ids
            $ids = $res->getWorkoutIds();
            // call workouts MS for data
            $client = new \GuzzleHttp\Client();
            $result = $client->request('GET', $this->configuration['workouts_url'] . '/workouts/ids?lang=' .$lang. '&state=R' . '&ids=' .$ids, []);
            $workoutsData = $result->getBody()->getContents();

            // get tags ids
            $tagIds = $res->getTags();
            // call tags MS for data
            $client = new \GuzzleHttp\Client();
            $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
            $tags = $result->getBody()->getContents();

            // check data and set response
            if(isset($id)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData([
                    'id' => $res->getId(),
                    'thumbnail' => $res->getThumbnail(),
                    'name' => $res->getName(),
                    'raw_name' => $res->getRawName(),
                    'language' => $res->getLang(),
                    'description' => $res->getDescription(),
                    'version' => $res->getVersion(),
                    'tags' => json_decode($tags),
                    'workouts' => json_decode($workoutsData)
                ]);
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get plan service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get list of workout plans
     *
     * @param int $from
     * @param int $limit
     * @return ResponseBootstrap
     */
    public function getListOfWorkoutPlans(int $from, int $limit, string $state = null, string $lang = null):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setFrom($from);
            $entity->setLimit($limit);
            $entity->setState($state);
            $entity->setLang($lang);

            // call mapper for data
            $data = $this->workoutPlansMapper->getList($entity);

            // set response according to data content
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get workoutplans list service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get plans service
     *
     * @param string $lang
     * @param string|null $app
     * @param string|null $like
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPlans(string $lang, string $state, string $app = null, string $like = null):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create facade and call its functions for data
            $facade = new GetPlansFacade($lang, $app, $like, $state, $this->workoutPlansMapper);
            $res = $facade->handlePlans();

            // check which data to collect
            if(gettype($res) === 'object'){
                // convert data to array for appropriate response
                $data = [];

                for($i = 0; $i < count($res); $i++){
                    $data[$i]['id'] = $res[$i]->getId();
                    $data[$i]['name'] = $res[$i]->getName();
                    $data[$i]['thumbnail'] = $res[$i]->getThumbnail();
                    $data[$i]['description'] = $res[$i]->getDescription();
                    $data[$i]['version'] = $res[$i]->getVersion();
                    // $data[$i]['state'] = $res[$i]->getState();

                    // get tags ids
                    $tagIds = $res[$i]->getTags();
                    // call workouts MS for data
                    $client = new \GuzzleHttp\Client();
                    $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
                    $tags = $result->getBody()->getContents();

                    $data[$i]['tags'] = json_decode($tags);

                    // get workout ids
                    $ids = $res[$i]->getWorkoutIds();
                    // call workouts MS for data
                    $client = new \GuzzleHttp\Client();
                    $result = $client->request('GET', $this->configuration['workouts_url'] . '/workouts/ids?lang=' .$lang. '&state=R' . '&ids=' .$ids, []);
                    $workoutsData = $result->getBody()->getContents();

                    $data[$i]['workouts'] = json_decode($workoutsData);
                }

            }else if(gettype($res) === 'array'){
                // if we collect data by app
                $data = $res;
            }

            // check data and set response
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get plans service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get plans by ids service
     *
     * @param array $ids
     * @param string $lang
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPlansById(array $ids, string $lang, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setIds($ids);
            $entity->setLang($lang);
            $entity->setState($state);

            // get response from database
            $res = $this->workoutPlansMapper->getPlansById($entity);

            // convert data to array for appropriate response
            $data = [];

            for($i = 0; $i < count($res); $i++){
                $data[$i]['id'] = $res[$i]->getId();
                $data[$i]['name'] = $res[$i]->getName();
                $data[$i]['raw_name'] = $res[$i]->getRawName();
                $data[$i]['thumbnail'] = $res[$i]->getThumbnail();
                $data[$i]['description'] = $res[$i]->getDescription();
                $data[$i]['version'] = $res[$i]->getVersion();
                // $data[$i]['state'] = $res[$i]->getState();

                // get tags ids
                $tagIds = $res[$i]->getTags();
                // call tags MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
                $tags = $result->getBody()->getContents();

                $data[$i]['tags'] = json_decode($tags);

                // get workout ids
                $ids = $res[$i]->getWorkoutIds();
                $data[$i]['ids'] = $ids;
                // call workouts MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['workouts_url'] . '/workouts/ids?lang=' .$lang. '&state=R' . '&ids=' .$ids, []);
                $workoutsData = $result->getBody()->getContents();

                $data[$i]['workouts'] = json_decode($workoutsData);
            }

            // check data and set response
            if($res->getStatusCode() == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get plans by ids service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Delete plan
     *
     * @param int $id
     * @return ResponseBootstrap
     */
    public function deletePlan(int $id):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setId($id);

            // get response from database
            $res = $this->workoutPlansMapper->deletePlan($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Delete plan service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Release plan service
     *
     * @param int $id
     * @return ResponseBootstrap
     */
    public function releasePlan(int $id):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setId($id);

            // get response from database
            $res = $this->workoutPlansMapper->releasePlan($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Release plan service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }

    }


    /**
     * Add plan
     *
     * @param NamesCollection $names
     * @param array $workouts
     * @param array $tags
     * @param string $thumbnail
     * @param string $rawName
     * @param string $type
     * @return ResponseBootstrap
     */
    public function createPlan(NamesCollection $names, array $workouts, array $tags, string $thumbnail,  string $rawName, string $type):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setTags($tags);
            $entity->setThumbnail($thumbnail);
            $entity->setNames($names);
            $entity->setWorkoutIds($workouts);
            $entity->setType($type);
            $entity->setName($rawName);

            // get response from database
            $res = $this->workoutPlansMapper->createPlan($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Create plan service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Edit plan service
     *
     * @param int $id
     * @param NamesCollection $names
     * @param array $workouts
     * @param array $tags
     * @param string $thumbnail
     * @param string $rawName
     * @param string $type
     * @return ResponseBootstrap
     */
    public function editPlan(int $id, NamesCollection $names, array $workouts, array $tags, string $thumbnail,  string $rawName, string $type):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setId($id);
            $entity->setTags($tags);
            $entity->setThumbnail($thumbnail);
            $entity->setNames($names);
            $entity->setWorkoutIds($workouts);
            $entity->setType($type);
            $entity->setName($rawName);

            // get response from database
            $res = $this->workoutPlansMapper->editPlan($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Edit plan service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get total number of workout plans
     *
     * @return ResponseBootstrap
     */
    public function getTotal():ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // call mapper for data
            $data = $this->workoutPlansMapper->getTotal();

            // check data and set response
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData([
                    $data
                ]);
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get total plans service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }
}