# Microsoft Dynamics CRM API dynamics SilverStripe 

## Using `sendBatchRequest` in CRM Integration

The `sendBatchRequest` function is a powerful function for interacting with CRM, allowing you to perform multiple operations in a single HTTP request. This method is particularly useful for applications that need to execute several CRUD (Create, Read, Update, Delete) operations on a CRM system efficiently. Below are examples, use cases, and advantages of using `sendBatchRequest`.

### How to Use `sendBatchRequest`

To use `sendBatchRequest`, you need to prepare an array of operations you wish to perform. Each operation in the array should specify the HTTP method (`POST`, `GET`, `DELETE`, etc.), the target URL (relative to the CRM base URL), and, if applicable, the data payload.

#### Example:

```php
use OP\CRM;

// Step 1: Define the operations
$operations = [
    [
        'method' => 'DELETE',
        'url' => "/Lead(LeadID=3)",
    ],
    [
        'method' => 'POST',
        'url' => "/Lead",
        'data' => [
            "FirstName" => "John",
            "LastName" => "Doe",
            "Email" => "john.doe@example.com",
            "Phone" => "123-456-7890",
        ],
    ],
];

// Step 2: Send the batch request
$response = CRM::sendBatchRequest($operations);

// Step 3: Optionally Handle the Fibre. If you want to check the fibre result, this will cause it to block
echo $response;
```

## you can handle other operations while `sendBatchRequest` executes in the background

sendBatchRequest sends requests using cURL in a Fiber for asynchronous execution. It sets up HTTP headers, including Content-Length and Authorization with a bearer token, and executes a cURL session to send the request. It checks for cURL errors and HTTP status codes outside the 200-299 range, logging any issues encountered using a LoggerInterface. After executing the request and handling errors, it closes the cURL session and returns the response. The operation is wrapped in a Fiber, allowing it to run concurrently with other tasks, and the Fiber is started before the method returns the Fiber object itself. This approach enhances performance by enabling non-blocking I/O operations.

### When to Use `sendBatchRequest`

- **Bulk Data Operations**: When you need to perform multiple operations on the CRM data, such as creating, updating, or deleting several records at once.
- **Efficiency**: To reduce the number of HTTP requests made to the CRM server, thereby minimizing network latency and improving the overall performance of your application.
- **Transactional Operations**: If your use case requires that multiple operations be treated as a single transaction (i.e., all succeed or all fail together).

### Why `sendBatchRequest` Is Great

- **Performance Optimization**: By bundling multiple operations into a single request, `sendBatchRequest` significantly reduces the overhead caused by HTTP request/response cycles.
- **Simplified Error Handling**: Handling errors becomes simpler because you only need to parse a single response, even when performing multiple operations.
- **Enhanced Scalability**: Applications that frequently interact with CRM data can scale more effectively, as the reduced number of HTTP requests lowers the strain on both the client and server resources.
- **Transactional Integrity**: For CRMs that support transactional batches, `sendBatchRequest` can ensure that a set of operations either all succeed or fail together, maintaining data integrity.


**Register your Azure application to communicate with CRM**
1. Create a user account in your Microsoft 365 environment to be used as the token generator for your web application (e.g. webtoken@your_organisation.onmicrosoft.com). 
2. Add the user account to Dynamics 365 preferably with full permissions.
3. In Microsoft Azure Active Directory, create a Native Application in the App Registrations area.
![Step 3](images/azure1.png)
4. Within your Native Application, go to Owners and add the user account
![Step 4](images/azure2.png)
5. Within your Native Application, go to 'Required permissions' and add 'Dynamics CRM Online'. You must then go to Dynamics' Delegated Permissions and check 'Access CRM Online as organization users'.
![Step 5](images/azure3.png)
6. Within your Native Application, go to 'Keys' and generate a new key. Be sure to save the generated value somewhere for later use.
![Step 6](images/azure4.png)
7. You should now have everything you need to use the CRM module.

# Using the Envornment version:
==================

**Add your application details into .env**

Create a .env file and add the following:


    AZUREAPPLICATIONCLIENT="XXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXX"
    AZUREAPPLICATIONSECRET="my secret key that came from azure portal"
    AZUREAPPLICATIONENDPOINT="https://login.microsoftonline.com/XXXXXXXXXXXXX/oauth2/token"
    AZUREAPPLICATIONRESOURCELOCATION="https://<myorganisationcrmname>.dynamics.com"

==================

**Use the Microsoft Dynamics 365 Web API**

https://msdn.microsoft.com/en-us/library/mt593051.aspx

==================

**Examples**

Post data to CRM
```php
	try {
	  CRM::request(
	    'https://your_organisation.crm5.dynamics.com/api/data/v8.2/leads',
	    'POST',
	    array(
	      "subject" => "Website Enquiry",
	      "emailaddress1" => $do->YourEmail,
	      "firstname" => $do->YourName,
	      "jobtitle" => $do->Message
	    )
	  );
	} catch ( Exception $e) {
	  throw new SS_HTTPResponse_Exception('failure to connect to crm: '.$e->getMessage());
	}
```

Retrieve data from CRM - return only firstname and lastname - only return the first 3 pages
```php
	try {
	  CRM::request(
	    'https://your_organisation.crm5.dynamics.com/api/data/v8.2/leads?$select=firstname,leadid',
	    'GET',
	    array(),
	    array('Prefer: odata.maxpagesize=3')
	  );
	} catch ( Exception $e) {
	    throw new SS_HTTPResponse_Exception('failure to connect to crm: '.$e->getMessage());
	}
```

Update a object's fields by ID
```php
	try {
	  CRM::request(
	    'https://your_organisation.crm5.dynamics.com/api/data/v8.2/leads(bf830ffd-2047-e711-8105-70106fa91921)',
	    'PATCH',
	    array(
	      "subject" => "123 Website Enquiry",
	      "email  address1" => $do->YourEmail,
	      "firstname" => $do->YourName,
	      "jobtitle" => $do->Message
	    )
	  );
	} catch ( Exception $e) {
	  throw new SS_HTTPResponse_Exception('failure to connect to crm: '.$e->getMessage());
	}
```

Update an individual field for a object by ID
```php
	try {
	  CRM::request(
	    'https://your_organisation.crm5.dynamics.com/api/data/v8.2/leads(bf830ffd-2047-e711-8105-70106fa91921)/subject',
	    'PUT',
	    array(
	      "value" => "321 Website Enquiry"
	    )
	  );
	} catch ( Exception $e) {
	  throw new SS_HTTPResponse_Exception('failure to connect to crm: '.$e->getMessage());
	}
```

Delete a object by ID
```php
	try {
	  CRM::request(
	    'https://your_organisation.crm5.dynamics.com/api/data/v8.2/leads(bf830ffd-2047-e711-8105-70106fa91921)',
	    'DELETE'
	  );
	} catch ( Exception $e) {
	  throw new SS_HTTPResponse_Exception('failure to connect to crm: '.$e->getMessage());
	}
```
