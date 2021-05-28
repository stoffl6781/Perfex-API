# Perfex-API
API to Create Leads from WEB over Post request
This is a Fork from https://perfex.dev/en/enviando-leads-para-o-perfex-crm-en/ and i created a Module, so you can simply install as Module, no Code changes are needed.

To create a new lead, it is necessary to send a POST with the fields you want to feed. However, in order to create a new lead, there are certain fields that are essential. Are they:

Assigned: Who owns the Lead
Source: The source the Lead is coming from
Status: Which status this Lead belongs to
Name: The name by which the Lead is identified
Email *: Lead identification email
* In the absence of the email, you can identify Lead by Phone
Telephone: The telephone number by which the Lead will be identified.
By sending some value in these fields a new lead will be created. Api uses email as its primary unique identifier and Telephone as its alternate identifier. If there is already a Lead in the system with the indicated email, it will be updated with the data sent in the POST.

To update a lead it is necessary to send one of the identification fields, Email or Phone (in the absence of Email). And the fields you want to update. If the Lead sent already has its email registered in PerfexCRM, the API will not create a new lead. But it will update the information of PerfexCRM replacing with the data contained in the POST.

Fields that can be fed
List of fields available to be sent by PerfexAPI to compose a new Lead or perform an update.

source	id	Field referring to the ID of data sources (Source) configured in Perfex CRM
assigned	id	Field referring to the ID of the users (Staff) configured in Perfex CRM
status	id	Field referring to the status ID of Leads configured in Perfex CRM
email	@	Lead email field to be registered or redeemed in Perfex CRM
name	text	Field referring to the First and Last Name of the Lead to be registered or redeemed in Perfex CRM
company	text	Field referring to the First and Last Name of the Lead to be registered or redeemed in Perfex CRM
title	text	Lead Position field to be registered or redeemed in Perfex CRM
Web site	url	Field referring to the Lead Website to be registered or redeemed in Perfex CRM
phonenumber	text	Lead Telephone field to be registered or redeemed in Perfex CRM
address	text	Lead field (Street, number, complement) of the Lead to be registered or redeemed in Perfex CRM
city	text	Lead City field to be registered or redeemed in Perfex CRM
state	text	Lead State field to be registered or redeemed in Perfex CRM
country	referer	Field referring to the Country of the Lead to be registered or redeemed in Perfex CRM. This value has a reference table based on the database of Perfex CRM itself.
zip	text	Lead zip code field to be registered or redeemed in Perfex CRM
tags	text	Field referring to Lead Tags to be registered or redeemed in Perfex CRM. The tags field accepts more than one value that can be separated by ','. To remove a tag it is necessary to enter '-' in front of the name of the tag to be removed.
description	textarea	Field referring to the Description to be added to the Lead to be registered or redeemed in Perfex CRM. This field has the property of adding the value sent in a newline below the value present in the Lead profile.
leads_?	text	Field referring to Lead's active “Custom Fields” to be registered or redeemed in Perfex CRM. The term used for pointing the field is the slug value of the table of custom fields. Each custom field must be sent separately, adding a new field in the webhook for each custom field to be sent.
Endpoint-specific functions leads_api
The PerfexAPI on the Leads_api endpoint has some functions that allow greater intelligence in data traffic. So it is possible for you to update or create only the Leads that are already in Perfex CRM or just the new ones. In addition, it is possible to return data referring to the fields or and the values ​​of the Lead fields.

Token	token = Password	The Token is required in all requests, if you do not send the token or send an invalid token, the API will return an error.
Block Create Lead	blockcreatelead = 1	This function checks if the email sent already exists in the database, if it exists, it can be updated, if it does not exist, it will not be created.
Block Update Lead	blockupdatelead = 1	This function checks if the email sent already exists in the database, if it exists, it will not be able to receive the update. If it exists, it will not be created.
Return Active Fields	returnactivefields = 1	This function returns all available values ​​for the fields: Status, Source and Assigned.
Return Custom Fields	returncustomfields = 1	This function returns all available values ​​for the global custom fields.
email = email consulted	When adding an email this function returns all available values ​​for the custom fields assigned to this Lead.
Return Info	returninfo = 1	This function returns all available global fields.
email = email consulted
value = 1	When adding an email, this function returns all available values ​​for all fields of the lead that belongs to the consulted email
