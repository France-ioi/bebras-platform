#!/usr/bin/env python3

"""
This script exercises all endpoints normally accessed during a contest,
and checks that the X-Backend-Hints headers are correctly set on each
response.
"""

import http.client
import http.cookies
import json
import urllib.request
import urllib.parse
import urllib.error
import re
import sys
import traceback


QuotedStringRe = re.compile("\"(?:\\\\.|[^\"\\\\])*\"")


def unquote(qs):
    qs = re.sub("^\"", '', qs)
    qs = re.sub("\"$", '', qs)
    qs = re.sub("\\\\(.)", '\\1', qs)
    return qs


def read_http_header_value(raw_value):
    return list(map(unquote, QuotedStringRe.findall(raw_value)))


class Transaction(object):

    def __init__(self, host, port=None, http_host=None, base=None):
        self.host = host
        self.port = port if port is not None else 80
        self.http_host = http_host if http_host is not None else host
        self.base = '' if base is None else base
        self.sid = None  # "e9avt4qvh4e06dqficqhv567u4"

    def post_generic_request(self, endpoint, params):
        # TODO: test params['SID'] = self.sid or clean up backend
        post_body = urllib.parse.urlencode(params)
        headers = {
            'Host': self.http_host,
            'Content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Accept': 'application/json'
        }
        if self.sid is not None:
            headers['Cookie'] = "contest2={}".format(self.sid)
        conn = http.client.HTTPConnection(self.host, self.port)
        path = urllib.parse.urljoin(self.base, endpoint)
        # print("base {}, endpoint {}, path {}".format(self.base, endpoint, path))
        conn.request('POST', path, post_body, headers)
        response = conn.getresponse()
        if response.status != 200:
            raise Exception(
                'bad response {} {}'.format(response.status, response.reason))
        # for header in response.getheaders():
        #     print(header)
        hints = response.getheader('X-Backend-Hints', "")
        cookies = http.cookies.SimpleCookie(response.getheader('Set-Cookie'))
        if self.sid is None and 'contest2' in cookies:
            self.sid = cookies['contest2'].value
        str_body = response.read().decode("UTF-8")
        try:
            body = json.loads(str_body)
            return body, hints
        except Exception as ex:
            print("JSON parsing error during request on {}".format(endpoint))
            print(str(params))
            print(str(ex))
            print("the server response follows:")
            print(str_body)
            raise ex

    def post_data_request(self, params):
        return self.post_generic_request('data.php', params)

    def post_answer_request(self, params):
        return self.post_generic_request('answer.php', params)

    def post_solutions_request(self, params):
        return self.post_generic_request('solutions.php', params)

    def checkHints(self, received, expected):
        """ checkHints takes the received header (string) and list of
            expected headers, prints any mismatches.
        """
        rec_list = read_http_header_value(received)
        for exp_value in expected:
            if exp_value not in rec_list:
                self.messages.append("missing: {}".format(exp_value))
        for rec_value in rec_list:
            if rec_value not in expected:
                self.messages.append("unexpected: {}".format(rec_value))

    def beginTest(self, name):
        self.test_name = name
        self.messages = list()

    def endTest(self):
        if self.messages:
            print("\033[31;1m{}\033[0m".format(self.test_name))
            for message in self.messages:
                print("\033[31m{}\033[0m".format(message))
        else:
            print("\033[32m{}\033[0m".format(self.test_name))

    def loadNewSession(self):
        self.beginTest('loadNewSession')
        body, hints = self.post_data_request({'action': 'loadSession'})
        if not body.get('success', False):
            raise Exception('loadSession: failed')
        if 'SID' not in body or body['SID'] != self.sid:
            raise Exception('loadSession: bad or missing SID')
        self.checkHints(
            hints,
            [
                "ClientIP.loadSession:new"
            ])
        self.endTest()

    def loadOldSession(self, check=True):
        self.beginTest('loadOldSession')
        body, hints = self.post_data_request({'action': 'loadSession'})
        if not body.get('success', False):
            raise Exception('loadSession: failed')
        if 'SID' not in body or body['SID'] != self.sid:
            raise Exception('loadSession: bad or missing SID')
        if check:
            self.checkHints(
                hints,
                [
                    "ClientIP.loadSession:found",
                    "SessionId({}):loadSession".format(self.sid)
                ])
        self.endTest()

    def destroySession(self, check=True):
        self.beginTest('destroySession')
        body, hints = self.post_data_request({'action': 'destroySession'})
        if not body.get('success', False):
            raise Exception('destroySession: failed')
        if 'SID' not in body or body['SID'] != self.sid:
            raise Exception('destroySession: bad or missing SID')
        if check:
            self.checkHints(
                hints,
                [
                    "ClientIP.destroySession"
                ])
        self.endTest()

    def loadPublicGroups(self):
        self.beginTest('loadPublicGroups')
        body, hints = self.post_data_request({'action': 'loadPublicGroups'})
        if not body.get('success', False):
            raise Exception('loadPublicGroups: failed')
        self.checkHints(
            hints, ["ClientIP.loadPublicGroups"])
        self.group_code = body['groups'][-1]['code']
        self.endTest()

    def checkNoPassword(self):
        self.beginTest('checkNoPassword')
        body, hints = self.post_data_request({
            'action': 'checkPassword'
        })
        if body.get('success', False):
            raise Exception('unexpected success')
        self.checkHints(
            hints, [
                "ClientIP.error",
                "ClientIP.checkPassword:fail"
            ])
        self.endTest()

    def checkGroupPassword(self):
        self.beginTest('checkGroupPassword')
        body, hints = self.post_data_request({
            'action': 'checkPassword',
            'password': self.group_code,
            'getTeams': False
        })
        if not body.get('success', False):
            raise Exception('checkPassword(group): failed')
        self.group = body
        self.group_id = body.get('groupID')
        self.checkHints(
            hints, [
                "ClientIP.checkPassword:pass",
                "Group({}):checkPassword".format(self.group_id)
            ])
        # {"groupID": "8506", "askGrade": true, "askStudentId": false, "askPhoneNumber": false,
        #  "success": true, "askEmail": false, "bRecovered": "0",
        #  "contestFolder": "2016_algorea_1_toaioxxapt", "teams": "",
        #  "fullFeedback": "1", "allowTeamsOfTwo": "0", "newInterface": "1",
        #  "contestName": "Entra\\u00eenement Algor\\u00e9a 2016 premier tour",
        #  "name": "Algor\\u00e9a 2016 premier tour : tous les niveaux",
        #  "contestID": "777116237588142336", "contestOpen": "Open",
        #  "contestShowSolutions": "1", "customIntro": null, "askGenre": true,
        #  "askZip": false, "nbUnlockedTasksInitial": "4",
        #  "nbMinutesElapsed": "478039", "bonusScore": "0", "subsetsSize": "0",
        #  "nbMinutes": "45", "contestVisibility": "Visible", "isPublic": "1"}
        self.endTest()

    def createTeam(self):
        self.beginTest('createTeam')
        body, hints = self.post_data_request({
            'action': 'createTeam',
            'contestants[0][lastName]': 'Anonymous',
            'contestants[0][firstName]': 'Anonymous',
            'contestants[0][genre]': '2',
            'contestants[0][email]': '',
            'contestants[0][zipCode]': ''
        })
        if not body.get('success', False):
            raise Exception('createTeam: failed')
        self.team_id = body.get('teamID')
        self.team_code = body.get('password')
        self.checkHints(
            hints, [
                "ClientIP.createTeam:public",
                "Group({}):createTeam".format(self.group_id)
            ])
        self.endTest()

    def checkTeamPassword(self):
        self.beginTest('checkTeamPassword')
        body, hints = self.post_data_request({
            'action': 'checkPassword',
            'password': self.team_code,
            'getTeams': False
        })
        if not body.get('success', False):
            raise Exception('failed')
        self.checkHints(
            hints, [
                "ClientIP.checkPassword:pass",
                "Team({}):checkPassword".format(self.team_id)
            ])
        self.endTest()

    def loadContestData(self):
        self.beginTest('loadContestData')
        body, hints = self.post_data_request({
            'action': 'loadContestData'
        })
        if not body.get('success', False):
            raise Exception('loadContestData: failed')
        self.checkHints(
            hints, [
                "ClientIP.loadContestData:pass",
                "Team({}):loadContestData".format(self.team_id)
            ])
        # {'success': True, 'teamPassword': '8rzmzsjn',
        #  'questionsData': {
        #    '274': {'name': 'Variables', 'order': 4, 'noAnswerScore': 0,
        #            'options': {}, 'minScore': 0, 'folder': 'algorea_2016',
        #            'ID': '274', 'key': '2016-FR-19-minmax-variables',
        #            'maxScore': 40, 'answerType': '0'}, ...},
        #  'answers': [], 'scores': [], 'timeUsed': '0', 'endTime': None}
        self.endTest()

    def getRemainingTime(self):
        self.beginTest('getRemainingTime')
        body, hints = self.post_data_request({
            'action': 'getRemainingTime',
            'teamID': self.team_id
        })
        if not body.get('success', False):
            raise Exception('getRemainingTime: failed')
        self.checkHints(
            hints, [
                "ClientIP.getRemainingTime:pass",
                "Team({}):getRemainingTime".format(self.team_id)
            ])
        # {'success': True, 'remainingTime': 2700}
        self.endTest()

    def closeContest(self):
        self.beginTest('closeContest')
        body, hints = self.post_data_request({
            'action': 'closeContest'
        })
        if not body.get('success', False):
            raise Exception('closeContest: failed')
        self.checkHints(
            hints, [
                "ClientIP.closeContest:pass",
                "Team({}):closeContest".format(self.team_id)
            ])
        self.endTest()

    def sendAnswer(self):
        self.beginTest('sendAnswer')
        body, hints = self.post_answer_request({
            'answers[270][answer]': '{"easy":"2 2 4 1","medium":"","hard":""}',
            # 'answers[270][sending]': "true",  # not used
            'answers[270][score]': 99999,
            'teamID': self.team_id,
            'teamPassword': self.team_code
        })
        if not body.get('success', False):
            raise Exception('sendAnswer: failed')
        self.checkHints(
            hints, [
                "ClientIP.answer:pass",
                "Team({}):answer".format(self.team_id)
            ])
        self.endTest()

    def getSolutions(self):
        self.beginTest('getSolutions')
        body, hints = self.post_solutions_request({'ieMode': 'false'})
        if not body.get('success', False):
            raise Exception('getSolutions: failed')
        self.checkHints(
            hints, [
                "ClientIP.solutions:pass",
                "Team({}):solutions".format(self.team_id)
            ])
        self.endTest()

    def run(self):
        try:
            self.loadNewSession()
            self.destroySession()
            self.loadPublicGroups()
            self.loadNewSession()
            self.checkNoPassword()
            self.checkGroupPassword()
            self.createTeam()
            self.loadOldSession()
            print('team code: {}'.format(self.team_code))
            self.checkTeamPassword()
            self.loadContestData()
            self.getRemainingTime()
            self.sendAnswer()
            self.closeContest()
            self.getSolutions()
        except Exception as ex:
            print("{}: caught {}".format(self.test_name, ex))
            traceback.print_exc(file=sys.stdout)


if __name__ == '__main__':
    Transaction(
        # host='concours.castor-informatique.fr',
        host='castor.home.epixode.fr',
        base='/contestInterface/',
        http_host='concours.castor-informatique.fr'
    ).run()

# body, hints = self.post_data_request({'action': 'checkPassword', 'password': '3m9trav3'})
