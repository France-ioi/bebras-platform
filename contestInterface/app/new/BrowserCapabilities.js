var caps = {

    serviceWorker: 'serviceWorker' in navigator,
    MessageChannel: 'MessageChannel' in window,
    localStorage: 'localStorage' in window

}
export default caps;