import UI from "../components";

var drawStars = function(id, nbStars, starWidth, rate, mode, nbStarsLocked) {
    UI.ContestHeader.addClassStars(id);

    function clipPath(coords, xClip) {
        var result = [[coords[0][0], coords[0][1]]];
        var clipped = false;
        for (var iCoord = 1; iCoord <= coords.length; iCoord++) {
            var x1 = coords[iCoord - 1][0];
            var y1 = coords[iCoord - 1][1];
            var x2 = coords[iCoord % coords.length][0];
            var y2 = coords[iCoord % coords.length][1];
            if (x2 > xClip) {
                if (!clipped) {
                    result.push([
                        xClip,
                        y1 + ((y2 - y1) * (xClip - x1)) / (x2 - x1)
                    ]);
                    clipped = true;
                }
            } else {
                if (clipped) {
                    result.push([
                        xClip,
                        y1 + ((y2 - y1) * (xClip - x1)) / (x2 - x1)
                    ]);
                    clipped = false;
                }
                result.push([x2, y2]);
            }
        }
        result.pop();
        return result;
    }

    function pathFromCoords(coords) {
        var result = "m" + coords[0][0] + "," + coords[0][1];
        for (var iCoord = 1; iCoord < coords.length; iCoord++) {
            var x1 = coords[iCoord - 1][0];
            var y1 = coords[iCoord - 1][1];
            var x2 = coords[iCoord][0];
            var y2 = coords[iCoord][1];
            result += " " + (x2 - x1) + "," + (y2 - y1);
        }
        result += "z";
        return result;
    }

    var fillColors = { normal: "white", locked: "#ddd", useless: "#ced" };
    var strokeColors = { normal: "black", locked: "#ddd", useless: "#444" };
    var starCoords = [
        [25, 60],
        [5, 37],
        [35, 30],
        [50, 5],
        [65, 30],
        [95, 37],
        [75, 60],
        [78, 90],
        [50, 77],
        [22, 90]
    ];
    var fullStarCoords = [
        [[5, 37], [35, 30], [50, 5], [65, 30], [95, 37], [75, 60], [25, 60]],
        [[22, 90], [50, 77], [78, 90], [75, 60], [25, 60]]
    ];
    UI.ContestHeader.setStar(id);
    var paper = new Raphael(id, starWidth * nbStars, starWidth * 0.95);
    for (var iStar = 0; iStar < nbStars; iStar++) {
        var scaleFactor = starWidth / 100;
        var deltaX = iStar * starWidth;
        var coordsStr = pathFromCoords(starCoords, iStar * 100);
        var starMode = mode;
        if (iStar >= nbStars - nbStarsLocked) {
            starMode = "locked";
        }

        paper
            .path(coordsStr)
            .attr({
                fill: fillColors[starMode],
                stroke: "none"
            })
            .transform("s" + scaleFactor + "," + scaleFactor + " 0,0 t" + deltaX / scaleFactor + ",0");

        var ratio = Math.min(1, Math.max(0, rate * nbStars - iStar));
        var xClip = ratio * 100;
        if (xClip > 0) {
            for (var iPiece = 0; iPiece < fullStarCoords.length; iPiece++) {
                var coords = clipPath(fullStarCoords[iPiece], xClip);
                var star = paper
                    .path(pathFromCoords(coords))
                    .attr({
                        fill: "#ffc90e",
                        stroke: "none"
                    })
                    .transform("s" + scaleFactor + "," + scaleFactor + " 0,0 t" + deltaX / scaleFactor + ",0");
            }
        }
        paper
            .path(coordsStr)
            .attr({
                fill: "none",
                stroke: strokeColors[starMode],
                "stroke-width": 5 * scaleFactor
            })
            .transform("s" + scaleFactor + "," + scaleFactor + " 0,0 t" + deltaX / scaleFactor + ",0");
    }
};

export default drawStars;
