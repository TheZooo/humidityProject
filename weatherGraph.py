#!/usr/bin/python
import sys
import Adafruit_DHT
import pymysql

while True:
    humidity, temperature = Adafruit_DHT.read_retry(11, 18)
    print ('Temp: {0:0.1f} C  Humidity: {1:0.1f} %'.format(temperature, humidity))

    db = pymysql.connect(host="localhost", user="root", passwd="raspberry", db="projects")
    cur = db.cursor(pymysql.cursors.DictCursor)
    db.autocommit(True)
    
    #SQL insert statement
    sql = f"INSERT INTO humTemp (humidity, temperature) VALUES ('{humidity}','{temperature}')"
    #sqlUpdate = f"UPDATE humTemp SET humidity = '{humidity}', temperature = '{temperature}'"
    #sqlClear = "DELETE FROM humTemp"

    cur.execute(sql)

    cur.close()
    db.close()
