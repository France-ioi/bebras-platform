function encode(str) {
    return btoa(utf8.encode(str));
}

export default {

    encode,

    url_encode: function(str) {
        return encode(str).replace('+', '-').replace('/', '_');
    }

}