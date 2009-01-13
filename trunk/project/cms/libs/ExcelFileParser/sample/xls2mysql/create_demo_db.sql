#######################################################
# Create sample database.
#
#######################################################

create database if not exists ParserSample;


#######################################################
# Select this database as default.
#
#######################################################

use ParserSample;

#######################################################
# Create sample table in database.
#
#	Columns in Excel file will be interpreted
#	as follows:
#		0 - Product name
#		1 - Product specification
#		2 - Product price
#		3 - Product retail price
#               4 - Product date  
#
#
#######################################################

create table if not exists SampleData (

	######
	# Product name

	name			varchar(100),
	
	######
	# Product specification

	specification	varchar(255),
	
	######
	# Product price

	price			float,
        
	######
	# Product Date          
        date                    timestamp


);
