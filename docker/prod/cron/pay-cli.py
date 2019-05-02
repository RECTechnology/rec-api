#!/usr/bin/env python3

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException
from time import sleep
import sys, re

def pay(driver, url, cardholder, pan, expirymonth, expiryyear, cvv2):
  ''' This function launches a chromium browser, pays and returns a tuple with the result or raises exception '''
  #options = Options()
  #options.add_argument("--start-maximized")
  #driver = webdriver.Chrome("/home/lluis/bin/chromedriver", chrome_options=options)

  driver.maximize_window()
  driver.get(url)

  wait = WebDriverWait(driver, 10)

  content = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="content"]')))

  if re.search("payment-invalid", content.get_attribute('innerHTML')) is not None:
    raise RuntimeError(content.get_attribute('innerHTML'))

  #input_email = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="shopper.email"]')))

  input_cardholder = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="cardHolderName"]')))
  input_pan = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="cardNumber"]')))
  input_expirymonth = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="payment_form"]/div[2]/input[3]')))
  input_expiryyear = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="payment_form"]/div[2]/input[4]')))
  input_cvv2 = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="payment_form"]/div[2]/input[5]')))

  button_pay = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="btsubmit"]')))

  input_cardholder.send_keys(cardholder)
  input_pan.send_keys(pan)
  input_expirymonth.send_keys(expirymonth)
  input_expiryyear.send_keys(expiryyear)
  input_cvv2.send_keys(cvv2)

  button_pay.click()
  sleep(5)

  result = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="content"]')))

  if re.search("payment has been accepted", result.get_attribute('innerHTML')) is not None:
    order = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="content"]/div/ul[1]/li[1]/em'))).get_attribute('innerHTML')
    transaction = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="content"]/div/ul[1]/li[2]/em'))).get_attribute('innerHTML')
    date = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="content"]/div/ul[2]/li[1]/em'))).get_attribute('innerHTML')
    cardnumber = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="content"]/div/ul[2]/li[2]/em'))).get_attribute('innerHTML')
    cardtype = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="content"]/div/ul[2]/li[3]/em'))).get_attribute('innerHTML')
    cardholder = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="content"]/div/ul[2]/li[4]/em'))).get_attribute('innerHTML')
    site = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="content"]/div/ul[3]/li[1]/em'))).get_attribute('innerHTML')
    amount = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="content"]/div/ul[3]/li[2]/em'))).get_attribute('innerHTML')
    return [order, transaction, date, cardnumber, cardtype, cardholder, site, amount]
  else:
    raise RuntimeError("unknown error happened")
  

url = sys.argv[1]
cardholder = sys.argv[2]
pan = sys.argv[3]
expirymonth = sys.argv[4]
expiryyear = sys.argv[5]
cvv2 = sys.argv[6]

try:
  #sys.stdout.write(",".join([url, cardholder, pan, expirymonth, expiryyear, cvv2]))
  driver = webdriver.Firefox(headless=true)
  result = pay(driver, url, cardholder, pan, expirymonth, expiryyear, cvv2)
  #sys.stdout.write(",success,%s\n" % ",".join(result))
  driver.close()
  sys.exit(0)
except (RuntimeError, TimeoutException):
  sys.stdout.write(",error,,,,,,,,\n")
  driver.close()
  sys.exit(1)

