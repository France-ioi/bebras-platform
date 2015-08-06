# script for multi-mechanize to simulate a contestant accessing a contest in
# three steps: 
#   - submitting a password
#   - submitting name, first name and gender
#   - loading contest data
#
# You need a valid password for a contest for this (set it in run() function).

import mechanize
import random
import time
import httplib
import urllib
import json

# nested dictionnaries in URL are not implemented in urllib

def recursive_urlencode(data):
    def r_urlencode(data, parent=None, pairs=None):
        if pairs is None:
            pairs = {}
        if parent is None:
            parents = []
        else:
            parents = parent

        for key, value in data.items():
            if hasattr(value, 'values'):
                parents.append(key)
                r_urlencode(value, parents, pairs)
                parents.pop()
            else:
                pairs[renderKey(parents + [key])] = renderVal(value)

        return pairs
    return urllib.urlencode(r_urlencode(data))


def renderKey(parents):
    depth, outStr = 0, ''
    for x in parents:
        str = "[%s]" if depth > 0 else "%s"
        outStr += str % renderVal(x)
        depth += 1
    return outStr


def renderVal(val):
    return urllib.quote(unicode(val))

class Transaction(object):
    def __init__(self):
        pass

    def loginGroup(self, password):
        options = {
            'password': password,
            'action': 'checkPassword',
            'getTeams': None
            }
        post_body=recursive_urlencode(options)
        start_timer = time.time()
        req = mechanize.Request(url='http://dyna-castor.eroux.fr/data.php', data=post_body)
        req.add_header('Content-type', 'application/x-www-form-urlencoded')
        resp = mechanize.urlopen(req)
        resjson = resp.read()
        try:
            self.custom_timers['loginGroup'] = time.time() - start_timer
        except AttributeError:
            pass
        res = json.loads(resjson)
        assert (res[u'success'] == True), 'success = false in loginGroup!'
        return int(res[u'groupID'])
        

    def createTeam(self, groupID, contestants):
        options = {
            'action': 'createTeam',
            'groupID': groupID,
            'contestants': contestants
            }
        post_body=recursive_urlencode(options)
        start_timer = time.time()
        req = mechanize.Request(url='http://dyna-castor.eroux.fr/data.php', data=post_body)
        req.add_header('Content-type', 'application/x-www-form-urlencoded')
        resp = mechanize.urlopen(req)
        resjson = resp.read()
        try:
            self.custom_timers['createTeam'] = time.time() - start_timer
        except AttributeError:
            pass
        res = json.loads(resjson)
        assert (res['success'] == True), 'success = false in createTeam!'

    def loadContestData(self):
        post_body=recursive_urlencode({'action': 'loadContestData'})
        start_timer = time.time()
        req = mechanize.Request(url='http://dyna-castor.eroux.fr/data.php', data=post_body)
        req.add_header('Content-type', 'application/x-www-form-urlencoded')
        resp = mechanize.urlopen(req)
        resjson = resp.read()
        res = json.loads(resjson)
        assert (res['success'] == True), 'success = false in loadContestData!'
        try:
            self.custom_timers['loadContestData'] = time.time() - start_timer
        except AttributeError:
            pass

    def run(self):
        groupID = self.loginGroup('bnx9bzvn')
        contestants = {0:{'lastName': 'testLastName', 'firstName': 'testFirstName', 'genre': 'testGenre'}}
        self.createTeam(35, contestants)
        self.loadContestData()


if __name__ == '__main__':
    trans = Transaction()
    trans.run()
    #print trans.custom_timers
