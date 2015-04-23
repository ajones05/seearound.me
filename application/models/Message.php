<?php

class Application_Model_MessageRow extends Zend_Db_Table_Row_Abstract

{

       

}



class Application_Model_Message extends My_Db_Table_Abstract

{

    protected $_name = "message";

    protected $_primary = "id";

    protected $_rowClass = "Application_Model_MessageRow";

    protected $_instance = null;

    protected $_filters = array(

        'sender_email' => array('StripTags', 'StringTrim'),

        'message' => array('StripTags', 'StringTrim'),

    );

    protected $_validators = array(

        'subject' => array(

			'allowEmpty' => false,

			array('StringLength', 0, 60),

			'messages' => array(

				array(

					Zend_Validate_StringLength::TOO_LONG => 'Subject is greater than 60 characters'

				)	

			)

		),

        'message' => array(

			'allowEmpty' => false,

			array('StringLength', 0, 400),

            	'messages' => array(

				array(

					Zend_Validate_StringLength::TOO_LONG => 'Message is greater than 400 characters'

				)	

			)

		),

    );

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'Receiver' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'receiver_id'
        )
    );

    public static function getInstance() 

    {

        if(null === self::$_instance) {

            self::$_instance = new self();

        }

        return self::$_instance;

    }

	/**
     * Returns an instance of a Zend_Db_Table_Select object.
     *
     * @param bool $withFromPart Whether or not to include the from part of the select based on the table
     * @return Zend_Db_Table_Select
     */
    public function publicSelect($withFromPart = self::SELECT_WITHOUT_FROM_PART)
    {
		return parent::select($withFromPart)->where('is_deleted =?', 'false')->where('is_valid =?', 'true');
    }

    public function validateData($request, &$data, &$errors) 

    {

        $messageTable = new Application_Model_Message();

        $data['user'] = array(

            'subject' => $request->getParam('subject', null),

            'message' => $request->getParam('message', null)

        );

     	if (($validatedErrors = $messageTable->validate($data['user'])) && ($validatedErrors !== true)) {

			$errors = $validatedErrors;

		}   

    }

    function saveData($data)

    {

        $messageTable = new Application_Model_Message();

        $row = $messageTable->createRow($data);

        $row->save();

        return $row;

    }

    

    function getAllData() {

        

    }

    

    function getUserData($data = array(), $reply=false) {

        $messageTable = new Application_Model_Message();

        $select = $messageTable->select()->setIntegrityCheck(false)

        	->from('message');

        if($data && is_array($data) && array_key_exists("receiver_id", $data)) {

            $select->joinLeft('user_data', 'user_data.id = message.sender_id', array('Name','Email_id'))

                ->where('message.receiver_id =?', $data['receiver_id'])

                ->where('message.is_deleted =?', 'false')

                ->where('message.is_valid =?', 'true');

        } else if($data && is_array($data) && array_key_exists("sender_id", $data)) {

            $select->joinLeft('user_data', 'user_data.id = message.sender_id', array('Name','Email_id'))

                ->where('message.sender_id =?', $data['sender_id'])

                ->where('message.is_deleted =?', 'false')

                ->where('message.is_valid =?', 'true');

        }

        if($reply && isset($data['sender_id'])) {

            $select->orWhere('reply_to =?',$data['sender_id']);

        } else if($reply && isset($data['receiver_id'])) {

            $select->orWhere('reply_to =?',$data['receiver_id']);

        }

        $select->order('updated DESC'); 
     
        if($row = $messageTable->fetchAll($select)) {

            return $row;

        }

    }
    
     function getUnreadUserMessage($data = array(), $reply=false) {
        $messageTable = new Application_Model_Message();
        $select = $messageTable->select()->setIntegrityCheck(false)
      	          ->from('message');
         
        if($data && is_array($data) && array_key_exists("receiver_id", $data)) {
            $select->joinLeft('user_data', 'user_data.id = message.sender_id', array('Name','Email_id','Profile_image','Birth_Date'))
                   ->joinLeft('user_profile', 'user_profile.id = message.sender_id', array('Activities','Gender'))
                   ->where('message.receiver_id =?', $data['receiver_id'])
                   ->where('message.is_deleted =?', 'false')
                   ->where('message.is_valid =?', 'true')
                   ->where('message.reciever_read =?','false');

        } else if($data && is_array($data) && array_key_exists("sender_id", $data)) {
            $select->joinLeft('user_data', 'user_data.id = message.sender_id', array('Name','Email_id','Profile_image','Birth_Date'))
                   ->joinLeft('user_profile', 'user_profile.id = message.sender_id', array('Activities','Gender'))
                   ->where('message.sender_id =?', $data['sender_id'])
                   ->where('message.is_deleted =?', 'false')
                   ->where('message.is_valid =?', 'true')
                   ->where('message.reciever_read =?','false');
        }

       /* if($reply && isset($data['sender_id'])) {
            $select->orWhere('reply_to =?',$data['sender_id']);
          } else if($reply && isset($data['receiver_id'])) {
            $select->orWhere('reply_to =?',$data['receiver_id']);
        } */

        $select->order('updated DESC'); 
        if($row = $messageTable->fetchAll($select)) {

            return $row;

        }

    }
    
    function getConversationMessage($data = array(), $reply=false) {
        $messageTable = new Application_Model_Message();
        $select = $messageTable->select()->setIntegrityCheck(false)->from('message');
        if($data && is_array($data) && array_key_exists("receiver_id", $data)) {
            $select->joinLeft('user_data', 'user_data.id = message.sender_id', array('Name','Email_id','Profile_image','Birth_Date'))
                   ->joinLeft('user_profile', 'user_profile.id = message.sender_id', array('Activities','Gender'))
                   ->where('message.receiver_id =?',  $data['receiver_id'])
                   ->where('message.sender_id =?', $data['sender_id'])
                   ->orWhere('message.receiver_id =?',  $data['sender_id'])
                   ->where('message.sender_id =?', $data['receiver_id'])
                   ->where('message.is_deleted =?', 'false')
                   ->where('message.is_valid =?', 'true');
                  // ->where('message.reciever_read =?','false');
         }
   
        $select->order('updated ASC'); 
      
        if($row = $messageTable->fetchAll($select)) {
           return $row;
        }

    }

    function viewed($id, $user_id) {
        $messageTable = new Application_Model_Message();
        $select = $messageTable->select()
            ->where('id =?', $id);
        if($row = $messageTable->fetchRow($select)) {
            if($row->receiver_id == $user_id) {
                $row->setFromArray(array('reciever_read' => 'true'));
            } else if($row->sender_id == $user_id) {
                $row->setFromArray(array('sender_read' => 'true'));
            }
            $row->save();
        }
        return $row; 
    }

    

    function getNoteMessage($id, $all=null) 

    {

        $select = $this->select();

        if($id) {

            $select->where('receiver_id =?', $id)

                ->where('is_deleted =?', 'false')

                ->where('is_valid =?', 'true')

                ->where('reciever_read =?', 'false')

                

                ->orWhere('(reply_to =?',$id)

                ->where('sender_read =?)', 'false');

        }

        return $this->fetchAll($select);

    }

}