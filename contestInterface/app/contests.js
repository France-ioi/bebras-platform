import fetch from './common/Fetch';


function getData(callback) {
    fetch(
        "data.php",
        {
			SID: app.SID,
			controller: "Contests",
            action: "getData",
        },
        function(data) {
            callback && callback(data);
        }
    );
/*

    var res = {
        contests: {
            practice: [
                {
                    languages: {
                        '1': 'blockly',
                        '2': 'python'
                    },
                    name: "Test white",
                    year: "2019",
                    type: "algorea_white"
                },
                {
                    languages: {
                        '3': ''
                    },
                    name: "Test green",
                    year: "2019",
                    type: "algorea_green",

                },
            ],

            open: [
                {
                    ID: 4,
                    name: "Test orange 1",
                    year: "2019",
                    type: "algorea_orange",

                },
                {
                    ID: 5,
                    name: "Test orange 2",
                    year: "2019",
                    type: "algorea_orange",

                },
            ],

            past: []

        },
        results: {
            1: {
                score: '1',
                date: '01/01/19'
            },
            2: {
                score: '2',
                date: '02/01/19'
            },
            3: {
                score: '3',
                date: '03/01/19'
            }
        }
    }
    callback(res)
    */
}


export default {
    getData
}