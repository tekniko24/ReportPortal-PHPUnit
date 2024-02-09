# ReportPortal-PHPUnit

A PHPUnit agent for Report Portal using the new events system

### **Setup:**

1. Add the following dependency to your `composer.json` file.

```
	"require-dev": {
		"tekniko/phpunitrpagent": "*"
	},
```

2. Add the following extension to your `phpunit.xml` file and replace the parameter values with what corresponds to your Report Portal Instance.

```
  <extensions>
    <bootstrap class="TekNiko\PHPUnitRPAgent\ReportPortalLoggerExtension">
      <parameter name="APIKey" value="phpunitagent_api_key"/>
      <parameter name="host" value="https://rp.epam.com"/>
      <parameter name="projectName" value="luis_cinco_personal"/>
      <parameter name="timeZone" value=".000+00:00"/>
      <parameter name="launchName" value="Test Launch Name"/>
      <parameter name="launchDescription" value="Test Launch Description"/>
    </bootstrap>
  </extensions>
```

| Parameter         | Description                                                                                                               |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------- |
| APIKey            | An API key for a user in your Report Portal instance that has access to the Project defined in the projectName parameter. |
| host              | The host URL of your Report Portal instance.                                                                              |
| projectName       | The  project where the test launches will be created.                                                                     |
| timeZone          | Timezone of where the test launches are executed in.                                                                      |
| launchName        | Test Launch Name                                                                                                          |
| launchDescription | Test Launch Description                                                                                                   |
|                   |                                                                                                                           |
