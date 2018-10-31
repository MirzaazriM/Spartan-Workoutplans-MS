<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/28/18
 * Time: 9:26 AM
 */

namespace Model\Mapper;

use Model\Entity\Shared;
use PDO;
use PDOException;
use Component\DataMapper;
use Model\Entity\Plan;
use Model\Entity\PlanCollection;

class WorkoutPlansMapper extends DataMapper
{

    public function getConfiguration()
    {
        return $this->configuration;
    }


    /**
     * Fetch plan
     *
     * @param Plan $plan
     * @return Plan
     */
    public function getPlan(Plan $plan):Plan {

        // create response object
        $response = new Plan();

        try {
            // set database instructions
            $sql = "SELECT
                       wp.id,
                       wp.thumbnail,
                       wp.type,
                       wp.state,
                       wp.raw_name,
                       wp.version,
                       wpd.description,
                       wpn.name,
                       wpn.language,
                       GROUP_CONCAT(DISTINCT wpw.workout_id) AS workout_ids,
                       GROUP_CONCAT(DISTINCT wpt.tag_id) AS tags
                    FROM workout_plans AS wp
                    LEFT JOIN workout_plans_descriptions AS wpd ON wp.id = wpd.workout_plans_parent
                    LEFT JOIN workout_plans_names AS wpn ON wp.id = wpn.workout_plans_parent
                    LEFT JOIN workout_plans_workouts AS wpw ON wp.id = wpw.workout_plans_parent
                    LEFT JOIN workout_plans_tags AS wpt ON wp.id = wpt.workout_plans_parent
                    WHERE wp.id = ?
                    AND wpn.language = ?
                    AND wpd.language = ?
                    AND wp.state = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getId(),
                $plan->getLang(),
                $plan->getLang(),
                $plan->getState()
            ]);

            // fetch data
            $data = $statement->fetch();

            // set entity values
            if($statement->rowCount() > 0){
                $response->setId($data['id']);
                $response->setThumbnail($this->configuration['asset_link'] . $data['thumbnail']);
                $response->setName($data['name']);
                $response->setRawName($data['raw_name']);
                $response->setType($data['type']);
                $response->setVersion($data['version']);
                $response->setState($data['state']);
                $response->setDescription($data['description']);
                $response->setLang($data['language']);
                $response->setWorkoutIds($data['workout_ids']);
                $response->setTags($data['tags']);
            }

        }catch(PDOException $e){
            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get plan mapper: " . $e->getMessage());
        }

        // return data
        return $response;
    }


    /**
     * Get plans list
     *
     * @param Plan $plan
     * @return array
     */
    public function getList(Plan $plan){

        try {

            // get state
            $state = $plan->getState();

            // check if state is set
            if($state === null or $state === ''){
                // set database instructions
                $sql = "SELECT
                       wp.id,
                       wp.state,
                       wp.version,
                       wp.raw_name,
                       wp.thumbnail,
                       wpn.name,
                       wpn.language
                    FROM workout_plans AS wp
                    LEFT JOIN workout_plans_names AS wpn ON wp.id = wpn.workout_plans_parent  
                   /* WHERE wpn.language = 'en' */
                    LIMIT :from,:limit";
                // set statement
                $statement = $this->connection->prepare($sql);
                // set from and limit as core variables
                $from = $plan->getFrom();
                $limit = $plan->getLimit();

                // bind parametars
                $statement->bindParam(':from', $from, PDO::PARAM_INT);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);

                // execute query
                $statement->execute();
            }else {
                // set database instructions
                $sql = "SELECT
                       wp.id,
                       wp.state,
                       wp.version,
                       wp.raw_name,
                       wp.thumbnail,
                       wpn.name,
                       wpn.language
                    FROM workout_plans AS wp
                    LEFT JOIN workout_plans_names AS wpn ON wp.id = wpn.workout_plans_parent  
                    WHERE wpn.language = :lang AND wp.state = :state
                    LIMIT :from,:limit";
                // set statement
                $statement = $this->connection->prepare($sql);
                // set from and limit as core variables
                $from = $plan->getFrom();
                $limit = $plan->getLimit();
                $language = $plan->getLang();

                // bind parametars
                $statement->bindParam(':from', $from, PDO::PARAM_INT);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
                $statement->bindParam(':state', $state);
                $statement->bindParam(':lang', $language);

                // execute query
                $statement->execute();
            }

            // set data
            $data = $statement->fetchAll(PDO::FETCH_ASSOC);

            // create formatted data variable
            $formattedData = [];

            // loop through data and add link prefixes
            foreach($data as $item){
                $item['thumbnail'] = $this->configuration['asset_link'] . $item['thumbnail'];

                // add formatted item in new array
                array_push($formattedData, $item);
            }

        }catch (PDOException $e){
            $formattedData = [];
            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get workoutplans list mapper: " . $e->getMessage());
        }

        // return data
        return $formattedData;
    }


    /**
     * Fetch plans
     *
     * @param Plan $plan
     * @return PlanCollection
     */
    public function getPlans(Plan $plan):PlanCollection {

        // create response object
        $planCollection = new PlanCollection();

        try {
            // set database instructions
            $sql = "SELECT
                       wp.id,
                       wp.thumbnail,
                       wp.type,
                       wp.state,
                       wp.version,
                       wpd.description,
                       wpn.name,
                       wpn.language,
                       GROUP_CONCAT(DISTINCT wpw.workout_id) AS workout_ids,
                       GROUP_CONCAT(DISTINCT wpt.tag_id) AS tags
                    FROM workout_plans AS wp
                    LEFT JOIN workout_plans_descriptions AS wpd ON wp.id = wpd.workout_plans_parent
                    LEFT JOIN workout_plans_names AS wpn ON wp.id = wpn.workout_plans_parent
                    LEFT JOIN workout_plans_workouts AS wpw ON wp.id = wpw.workout_plans_parent
                    LEFT JOIN workout_plans_tags AS wpt ON wp.id = wpt.workout_plans_parent
                    WHERE wpn.language = ?
                    AND wpd.language = ?
                    AND wp.state = ?
                    GROUP BY wp.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getLang(),
                $plan->getLang(),
                $plan->getState()
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create new plan
                $plan = new Plan();

                // set plan values
                $plan->setId($row['id']);
                $plan->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $plan->setName($row['name']);
                $plan->setType($row['type']);
                $plan->setVersion($row['version']);
                $plan->setState($row['state']);
                $plan->setDescription($row['description']);
                $plan->setLang($row['language']);
                $plan->setWorkoutIds($row['workout_ids']);
                $plan->setTags($row['tags']);

                // add plan to collection
                $planCollection->addEntity($plan);
            }

            // set status code
            if($statement->rowCount() == 0){
                $planCollection->setStatusCode(204);
            }else {
                $planCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $planCollection->setStatusCode(204);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get plans mapper: " . $e->getMessage());
        }

        // return data
        return $planCollection;
    }


    /**
     * Get plans by search term
     *
     * @param Plan $plan
     * @return PlanCollection
     */
    public function searchPlans(Plan $plan):PlanCollection {

        // create response object
        $planCollection = new PlanCollection();

        try {
            // set database instructions
            $sql = "SELECT
                       wp.id,
                       wp.thumbnail,
                       wp.type,
                       wp.state,
                       wp.version,
                       wpd.description,
                       wpn.name,
                       wpn.language,
                       GROUP_CONCAT(DISTINCT wpw.workout_id) AS workout_ids,
                       GROUP_CONCAT(DISTINCT wpt.tag_id) AS tags
                    FROM workout_plans AS wp
                    LEFT JOIN workout_plans_descriptions AS wpd ON wp.id = wpd.workout_plans_parent
                    LEFT JOIN workout_plans_names AS wpn ON wp.id = wpn.workout_plans_parent
                    LEFT JOIN workout_plans_workouts AS wpw ON wp.id = wpw.workout_plans_parent
                    LEFT JOIN workout_plans_tags AS wpt ON wp.id = wpt.workout_plans_parent
                    WHERE wpn.language = ?
                    AND wpd.language = ?
                    AND wp.state = ?
                    AND (wpn.name LIKE ? OR wpd.description LIKE ?)
                    GROUP BY wp.id";
            $term = '%' . $plan->getName() . '%';
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getLang(),
                $plan->getLang(),
                $plan->getState(),
                $term,
                $term
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create new plan
                $plan = new Plan();

                // set plan values
                $plan->setId($row['id']);
                $plan->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $plan->setName($row['name']);
                $plan->setType($row['type']);
                $plan->setVersion($row['version']);
                $plan->setState($row['state']);
                $plan->setDescription($row['description']);
                $plan->setLang($row['language']);
                $plan->setWorkoutIds($row['workout_ids']);
                $plan->setTags($row['tags']);

                // add plan to the collection
                $planCollection->addEntity($plan);
            }

            // set entity values
            if($statement->rowCount() == 0){
                $planCollection->setStatusCode(204);
            }else {
                $planCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $planCollection->setStatusCode(204);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Search plans mapper: " . $e->getMessage());
        }

        // return data
        return $planCollection;
    }


    /**
     * Fetch plans by ids
     *
     * @param Plan $plan
     * @return PlanCollection
     */
    public function getPlansById(Plan $plan):PlanCollection {

        // create response object
        $planCollection = new PlanCollection();

        // convert ids array to comma separated string
        $whereIn = $this->sqlHelper->whereIn($plan->getIds());

        try {
            // set database instructions
            $sql = "SELECT
                       wp.id,
                       wp.raw_name,
                       wp.thumbnail,
                       wp.type,
                       wp.state,
                       wp.version,
                       wpd.description,
                       wpn.name,
                       wpn.language,
                       GROUP_CONCAT(DISTINCT wpt.tag_id) AS tags
                    FROM workout_plans AS wp
                    LEFT JOIN workout_plans_descriptions AS wpd ON wp.id = wpd.workout_plans_parent
                    LEFT JOIN workout_plans_names AS wpn ON wp.id = wpn.workout_plans_parent
                    LEFT JOIN workout_plans_workouts AS wpw ON wp.id = wpw.workout_plans_parent
                    LEFT JOIN workout_plans_tags AS wpt ON wp.id = wpt.workout_plans_parent
                    WHERE wp.id IN (" . $whereIn . ")
                    AND wpn.language = ?
                    AND wpd.language = ?
                    AND wp.state = ?
                    GROUP BY wp.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getLang(),
                $plan->getLang(),
                $plan->getState()
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create new plan
                $plan = new Plan();

                // set plan values
                $plan->setId($row['id']);
                $plan->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $plan->setName($row['name']);
                $plan->setRawName($row['raw_name']);
                $plan->setType($row['type']);
                $plan->setVersion($row['version']);
                $plan->setState($row['state']);
                $plan->setDescription($row['description']);
                $plan->setLang($row['language']);

                // get workout ids
                $sqlIds = "SELECT workout_id FROM workout_plans_workouts WHERE workout_plans_parent = ?";
                $statementIds = $this->connection->prepare($sqlIds);

                $statementIds->execute([
                    $row['id']
                ]);

                $idsData = $statementIds->fetchAll(PDO::FETCH_ASSOC);
                $idArray = [];
                foreach($idsData as $idD) {
                    array_push($idArray, $idD['workout_id']);
                }

                $plan->setWorkoutIds(implode(',', $idArray));
                $plan->setTags($row['tags']);

                // add plan to the collection
                $planCollection->addEntity($plan);
            }

            // set status code
            if($statement->rowCount() == 0){
                $planCollection->setStatusCode(204);
            }else {
                $planCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $planCollection->setStatusCode(204);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get plans by ids mapper: " . $e->getMessage());
        }

        // return data
        return $planCollection;
    }


    /**
     * Delete record
     *
     * @param Plan $plan
     * @return Shared
     */
    public function deletePlan(Plan $plan):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // set database instructions
            $sql = "DELETE
                      wp.*,
                      wpa.*,
                      wpn.*,
                      wpna.*,
                      wpd.*,
                      wpda.*,
                      wpw.*,
                      wpt.*
                    FROM workout_plans AS wp
                    LEFT JOIN workout_plans_audit AS wpa ON wp.id = wpa.workout_plans_parent
                    LEFT JOIN workout_plans_names AS wpn ON wp.id = wpn.workout_plans_parent
                    LEFT JOIN workout_plans_names_audit AS wpna ON wpn.id = wpna.workout_plans_names_parent
                    LEFT JOIN workout_plans_descriptions AS wpd ON wp.id = wpd.workout_plans_parent
                    LEFT JOIN workout_plans_descriptions_audit AS wpda ON wpd.id = wpda.workout_plans_descriptions_parent
                    LEFT JOIN workout_plans_workouts AS wpw ON wp.id = wpw.workout_plans_parent
                    LEFT JOIN workout_plans_tags AS wpt ON wp.id = wpt.workout_plans_parent
                    WHERE wp.id = ?
                    AND wp.state != 'R'";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getId()
            ]);

            // set status code
            if($statement->rowCount() > 0){
                $shared->setResponse([200]);
            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Delete plan mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Release plan
     *
     * @param Plan $plan
     * @return Shared
     */
    public function releasePlan(Plan $plan):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // set database instructions
            $sql = "UPDATE 
                      workout_plans  
                    SET state = 'R'
                    WHERE id = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getId()
            ]);

            // set response values
            if($statement->rowCount() > 0){
                // set response status
                $shared->setResponse([200]);

                // get latest version value
                $version = $this->lastVersion();

                // set new version of the workout
                $sql = "UPDATE workout_plans SET version = ? WHERE id = ?";
                $statement = $this->connection->prepare($sql);
                $statement->execute(
                    [
                        $version,
                        $plan->getId()
                    ]
                );

            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Release plan mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Add plan
     *
     * @param Plan $plan
     * @return Shared
     */
    public function createPlan(Plan $plan):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // get newest id for the version column
            $version = $this->lastVersion();

            // set database instructions for workout plans table
            $sql = "INSERT INTO workout_plans
                      (thumbnail, raw_name, type, state, version)
                     VALUES (?,?,?,?,?)";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getThumbnail(),
                $plan->getName(),
                $plan->getType(),
                'P',
                $version
            ]);

            // if first transaction passed continue with rest of inserting
            if($statement->rowCount() > 0){
                // get workout parent id
                $workoutParent = $this->connection->lastInsertId();

                // insert workout plan name
                $sqlName = "INSERT INTO workout_plans_names
                              (name, language, workout_plans_parent)
                            VALUES (?,?,?)";
                $statementName = $this->connection->prepare($sqlName);

                // insert workout plan description
                $sqlDescription = "INSERT INTO workout_plans_descriptions
                                     (description, language, workout_plans_parent)
                                   VALUES (?,?,?)";
                $statementDescription = $this->connection->prepare($sqlDescription);

                // loop through names collection
                $names = $plan->getNames();
                foreach($names as $name){
                    // execute queries
                    $statementName->execute([
                        $name->getName(),
                        $name->getLang(),
                        $workoutParent
                    ]);

                    $statementDescription->execute([
                        $name->getDescription(),
                        $name->getLang(),
                        $workoutParent
                    ]);
                }

                // insert workout plans workouts
                $sqlWorkouts = "INSERT INTO workout_plans_workouts
                                (workout_plans_parent, workout_id)
                              VALUES (?,?)";
                $statementWorkouts = $this->connection->prepare($sqlWorkouts);

                // loop through workout ids
                $ids = $plan->getWorkoutIds();
                foreach($ids as $id){
                    // execute query
                    $statementWorkouts->execute([
                        $workoutParent,
                        $id
                    ]);
                }

                // insert workout tags
                $sqlTags = "INSERT INTO workout_plans_tags
                                (workout_plans_parent, tag_id)
                              VALUES (?,?)";
                $statementTags = $this->connection->prepare($sqlTags);

                // loop through rounds collection
                $tags = $plan->getTags();
                foreach($tags as $tag){
                    // execute query
                    $statementTags->execute([
                        $workoutParent,
                        $tag
                    ]);
                }

                // set response code
                $shared->setResponse([200]);

            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of any failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Create plan mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Update plan
     *
     * @param Plan $plan
     * @return Shared
     */
    public function editPlan(Plan $plan):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // update main workout plans table
            $sql = "UPDATE workout_plans SET 
                        thumbnail = ?,
                        raw_name = ?,
                        type = ? 
                    WHERE id = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getThumbnail(),
                $plan->getName(),
                $plan->getType(),
                $plan->getId()
            ]);

            // update version
            if($statement->rowCount() > 0){
                // get last version
                $lastVersion = $this->lastVersion();

                // set database instructions
                $sql = "UPDATE workout_plans SET version = ? WHERE id = ?";
                $statement = $this->connection->prepare($sql);
                $statement->execute([
                    $lastVersion,
                    $plan->getId()
                ]);
            }

            // update names query
            $sqlNames = "INSERT INTO
                                workout_plans_names (name, language, workout_plans_parent)
                                VALUES (?,?,?)
                            ON DUPLICATE KEY
                            UPDATE
                                name = VALUES(name),
                                language = VALUES(language),
                                workout_plans_parent = VALUES(workout_plans_parent)";
            $statementNames = $this->connection->prepare($sqlNames);

            // update description query
            $sqlDescription = "INSERT INTO
                                    workout_plans_descriptions (description, language, workout_plans_parent)
                                    VALUES (?,?,?)
                                 ON DUPLICATE KEY
                                 UPDATE
                                    description = VALUES(description),
                                    language = VALUES(language),
                                    workout_plans_parent = VALUES(workout_plans_parent)";
            $statementDescription = $this->connection->prepare($sqlDescription);

            // loop through data and make updates if neccesary
            $names = $plan->getNames();
            foreach($names as $name){
                // execute name query
                $statementNames->execute([
                    $name->getName(),
                    $name->getLang(),
                    $plan->getId()
                ]);

                // execute description query
                $statementDescription->execute([
                    $name->getDescription(),
                    $name->getLang(),
                    $plan->getId()
                ]);
            }


            // delete workout ids
            $sqlDeleteWorkout = "DELETE FROM workout_plans_workouts WHERE workout_plans_parent = ?";
            $statementDeleteWorkout = $this->connection->prepare($sqlDeleteWorkout);
            $statementDeleteWorkout->execute([
                $plan->getId()
            ]);

            // update workout ids
            $sqlWorkouts = "INSERT INTO
                                workout_plans_workouts (workout_plans_parent, workout_id)
                                VALUES (?,?)
                            ON DUPLICATE KEY
                            UPDATE
                                workout_plans_parent = VALUES(workout_plans_parent),
                                workout_id = VALUES(workout_id)";
            $statementWorkouts = $this->connection->prepare($sqlWorkouts);

            // loop through data and make updates if neccesary
            $workouts = $plan->getWorkoutIds();
            foreach($workouts as $workout){
                // execute query
                $statementWorkouts->execute([
                    $plan->getId(),
                    $workout
                ]);
            }

            // delete tag ids
            $sqlDeleteTag = "DELETE FROM workout_plans_tags WHERE workout_plans_parent = ?";
            $statementDeleteTag = $this->connection->prepare($sqlDeleteTag);
            $statementDeleteTag->execute([
                $plan->getId()
            ]);

            // update tags
            $sqlTags = "INSERT INTO
                            workout_plans_tags (workout_plans_parent, tag_id)
                            VALUES (?,?)
                        ON DUPLICATE KEY
                        UPDATE
                            workout_plans_parent = VALUES(workout_plans_parent),
                            tag_id = VALUES(tag_id)";
            $statementTags = $this->connection->prepare($sqlTags);

            // loop through data and make updates if neccesary
            $tags = $plan->getTags();
            foreach($tags as $tag){
                // execute query
                $statementTags->execute([
                    $plan->getId(),
                    $tag
                ]);
            }

            // commit transaction
            $this->connection->commit();

            // set response status
            $shared->setResponse([200]);

        }catch(PDOException $e){
            // rollback everything n case of any failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Edit plan mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Get total number of workout plans
     *
     * @return null
     */
    public function getTotal() {

        try {
            // set database instructions
            $sql = "SELECT COUNT(*) as total FROM workout_plans";
            $statement = $this->connection->prepare($sql);
            $statement->execute();

            // get data
            $total = $statement->fetch(PDO::FETCH_ASSOC)['total'];

        }catch(PDOException $e){
            $total = null;

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get total plans mapper: " . $e->getMessage());
        }

        // return data
        return $total;
    }


    /**
     * Get last version number
     *
     * @return string
     */
    public function lastVersion(){
        // set database instructions
        $sql = "INSERT INTO version VALUES(null)";
        $statement = $this->connection->prepare($sql);
        $statement->execute([]);

        // fetch id
        $lastId = $this->connection->lastInsertId();

        // return last id
        return $lastId;
    }
}