# This script simulates the submission of an answer to answer.php. You need
# to set answer.php to accept any submission before testing this, as all
# data are random (teamId, questionId and answer).

import mechanize
import random
import time
import httplib
import urllib

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

    def run(self):
        options = {
            'teamID': random.randint(100,500),
            'teamPassword': 0,
            'answers': {random.randint(100,500): random.randint(0,10)}
            }
        post_body=recursive_urlencode(options)
        headers = {'Content-type': 'application/x-www-form-urlencoded'}
        conn = httplib.HTTPConnection('dyna-castor.eroux.fr:80')
        conn.request('POST', '/answer.php', post_body, headers)
        resp = conn.getresponse()
        resp.read()
        assert (resp.status == 200), 'Bad Response: HTTP %s' % resp.status


if __name__ == '__main__':
    trans = Transaction()
    trans.run()
